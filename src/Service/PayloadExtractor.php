<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Cryonighter\ValidRequestBundle\Validator\ExtendedCollection;
use Cryonighter\ValidRequestBundle\Validator\NoExtraFields;
use Cryonighter\ValidRequestBundle\Utils\NumberFormatParser;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class PayloadExtractor
{
    public function __construct(
        private ValidatorInterface $validator,
        private ViolationTransformer $violationTransformer,
        private ReflectionUtilService $reflectionUtilService,
        private iterable $patchers,
    ) {
    }

    public function resolveGroups(ReflectionClass $reflectionClass, ValidRequest $validRequestAttribute): array
    {
        return $validRequestAttribute->groups ?: array_filter(['Default', $this->getGroupSequence($reflectionClass)]);
    }

    /**
     * @throws ReflectionException
     * @throws ValidationFailedException
     */
    public function extractValidPayload(
        ReflectionClass $reflectionClass,
        ValidRequest $validRequestAttribute,
        Request $request,
        bool $allowedNull,
    ): ?array {
        $payload = match ($validRequestAttribute->source) {
            RequestValueSource::attributes => $request->attributes->all(),
            RequestValueSource::files => $request->files->all(),
            RequestValueSource::cookies => $request->cookies->all(),
            RequestValueSource::headers => $request->headers->all(),
            RequestValueSource::payload => $request->getPayload()->all(),
            RequestValueSource::server => $request->server->all(),
            RequestValueSource::query => $request->query->all(),
        };

        if (empty($payload) && $allowedNull) {
            return null;
        }

        $properties = $reflectionClass->getProperties();

        $notExpectedFields = $validRequestAttribute->source == RequestValueSource::payload ?
            $this->notExpectedFields($payload, $properties, $validRequestAttribute) : [];

        $data = $this->addMissingFields($payload, $properties, $request, $validRequestAttribute);
        $constraints = $this->getPropertiesAttributes($properties, $notExpectedFields, $validRequestAttribute);
        $groups = $this->resolveGroups($reflectionClass, $validRequestAttribute);

        if ($validRequestAttribute?->patcher) {
            $patcher = array_find(
                iterator_to_array($this->patchers),
                fn (PatcherInterface $patcher) => $validRequestAttribute->patcher === $patcher::class,
            );

            if (empty($patcher)) {
                throw new RuntimeException('Patcher service "' . $validRequestAttribute->patcher . '" not found');
            }

            $data = $patcher->patch($data, $request);
        }

        $violations = $this->violationTransformer->arrayKeyToObjectKey(
            $this->validator->validate($data, $constraints, $groups),
        );

        if ($violations->count() != 0) {
            throw new ValidationFailedException($data, $violations);
        }

        return $data;
    }

    private function getGroupSequence(ReflectionClass $reflectionClass): ?Assert\GroupSequence
    {
        return array_find(
            $reflectionClass->getAttributes(),
            fn (ReflectionAttribute $attribute): bool => $attribute->getName() == Assert\GroupSequence::class,
        )?->newInstance();
    }

    /**
     * @param ReflectionProperty[] $properties
     * @param ValidRequest $validRequest
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function getObjectStruct(array $properties, ValidRequest $validRequest): array
    {
        $data = [];

        foreach ($properties as $property) {
            if ($property->isVirtual()) {
                continue;
            }

            $name = $this->reflectionUtilService->getFieldName($property, $validRequest);
            $type = $property->getType();
            $typeName = $type->getName();

            if (!$type->isBuiltin()) {
                $data[$name] = $this->getObjectStruct(new ReflectionClass($typeName)->getProperties(), $validRequest);
            } else {
                $data[$name] = $typeName;
            }
        }

        return $data;
    }

    /**
     * @param array $payload
     * @param ReflectionProperty[] $properties
     * @param Request $request
     * @param ValidRequest $validRequest
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function addMissingFields(array $payload, array $properties, Request $request, ValidRequest $validRequest): array
    {
        $data = [];

        foreach ($properties as $property) {
            if ($property->isVirtual()) {
                continue;
            }

            $bindRequestParam = $this->reflectionUtilService->getBindRequestParamByGroups($property, $validRequest);

            $source = $bindRequestParam?->source;
            $name = $bindRequestParam?->name ?: $property->getName();
            $type = $property->getType();

            $value = $this->getRequestValue($name, $payload, $source, $request, $validRequest);

            if ($bindRequestParam?->patcher) {
                foreach ($this->patchers as $patcher) {
                    if ($bindRequestParam->patcher === $patcher::class) {
                        $data[$name] = $patcher->patch($value, $request);

                        continue(2);
                    }
                }

                throw new RuntimeException('Patcher service "' . $bindRequestParam->patcher . '" not found');
            }

            if ($value instanceof UploadedFile) {
                $data[$name] = $value;

                continue;
            }

            $hasAssertValid = !empty($property->getAttributes(Assert\Valid::class));

            // Если это невалидируемый объект или nullable объект - то внутреннюю структуру инициализировать не нужно
            if ($value === null && !$type->isBuiltin() && ($type->allowsNull() || !$hasAssertValid)) {
                $data[$name] = null;

                continue;
            }

            // Если это валидируемый not nullable объект и данные являются массивом или отсутствуют - инициализируем внутреннюю структуру
            if (!$type->isBuiltin() && $hasAssertValid && (is_array($value) || $value === null)) {
                $data[$name] = $this->addMissingFields($value ?? [], new ReflectionClass($type->getName())->getProperties(), $request, $validRequest);

                continue;
            }

            // Если это массив
            if ($type->getName() == 'array' && is_array($value)) {
                $typeFromAssertAll = $this->reflectionUtilService->getTypeFromAssertAll($property);

                // Если это массив объектов
                // TODO: а еще $typeFromAssertAll может быть массивом классов
                if (is_string($typeFromAssertAll) && class_exists($typeFromAssertAll)) {
                    $properties = new ReflectionClass($typeFromAssertAll)->getProperties();

                    $data[$name] = array_map(
                        fn(mixed $item): mixed => is_array($item) ? $this->addMissingFields($item, $properties, $request, $validRequest) : $item,
                        $value,
                    );

                    continue;
                }
            }

            // TODO: Проверить application/xml и text/xml (ну и multipart/form-data заодно)
            $isStringProtocol = $source != RequestValueSource::payload || $request->getContentTypeFormat() == 'form';

            // Если это bool
            if ($type->getName() == 'bool' && is_string($value) && $isStringProtocol && trim($value) === $value) {
                if (in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true)) {
                    $data[$name] = true;
                    continue;
                }

                if (in_array(strtolower($value), ['0', 'false', 'off', 'no'], true)) {
                    $data[$name] = false;
                    continue;
                }
            }

            // Если это int/float
            // TODO: проверить на псевдонимы, оставить комментарий если и так работает
            if (in_array($type->getName(), ['int', 'float']) && is_string($value) && $isStringProtocol && trim($value) === $value) {
                $numericValue = match ($type->getName()) {
                    'int' => NumberFormatParser::parseInt($value),
                    'float' => NumberFormatParser::parseFloat($value),
                };

                if ($numericValue !== null) {
                    $data[$name] = $numericValue;

                    continue;
                }
            }

            // Если пустая строка
            if ($value === '' && $isStringProtocol) {
                if ($type->getName() != 'string') {
                    $data[$name] = $this->reflectionUtilService->getPropertyDefaultValue($property);

                    continue;
                }

                if ($type->allowsNull()) {
                    $data[$name] = $this->reflectionUtilService->getPropertyDefaultValue($property);

                    continue;
                }
            }

            // Во всех остальных случаях
            $data[$name] = $value ?? $this->reflectionUtilService->getPropertyDefaultValue($property);
        }

        // Объединение с payload нужно, чтоб добавить поля, не описанные в объекте, но присутствующие в запросе
        return array_merge($payload, $data);
    }

    /**
     * @param ReflectionProperty[] $properties
     * @param array $notExpectedFields
     * @param ValidRequest $validRequest
     *
     * @return ExtendedCollection
     *
     * @throws ReflectionException
     */
    private function getPropertiesAttributes(array $properties, array $notExpectedFields, ValidRequest $validRequest): ExtendedCollection
    {
        $constraints = [];

        foreach ($properties as $property) {
            if ($property->isVirtual()) {
                continue;
            }

            $name = $this->reflectionUtilService->getFieldName($property, $validRequest);
            $type = $property->getType();

            if (!$type->isBuiltin() && $property->getAttributes(Assert\Valid::class)) {
                $constraints[$name] = $this->getPropertiesAttributes(
                    new ReflectionClass($type->getName())->getProperties(),
                    $notExpectedFields[$name] ?? [],
                    $validRequest,
                );

                continue;
            }

            if ($type->getName() == 'array' && $property->getAttributes(Assert\All::class)) {
                $typeDefaultMessage = new ReflectionProperty(Assert\Type::class, 'message')->getDefaultValue();
                $constraints[$name] = [];

                foreach ($property->getAttributes(Assert\All::class) as $all) {
                    $allConstraints = [];

                    /** @var Constraint $constraint */
                    foreach ($all->newInstance()->constraints as $constraint) {
                        if ($this->isRequiredAssert($constraint::class)) {
                            // TODO: нужно добавить кейс, где $constraint->type - массив типов
                            if ($constraint instanceof Assert\Type && is_string($constraint->type) && class_exists($constraint->type)) {
                                $allConstraints[] = new Assert\Type(
                                    'array',
                                    $constraint->message != $typeDefaultMessage ? $constraint->message : 'This value should be of type object.',
                                    $constraint->groups,
                                    $constraint->payload,
                                );
                                $allConstraints[] = $this->getPropertiesAttributes(
                                    new ReflectionClass($constraint->type)->getProperties(),
                                    [],
                                    $validRequest,
                                );
                            } else {
                                $allConstraints[] = $constraint;
                            }
                        }
                    }

                    if (!$allConstraints) {
                        continue;
                    }

                    $constraints[$name][] = new Assert\All($allConstraints);
                }
            }

            $propertyConstraints = [];

            foreach ($property->getAttributes() as $attribute) {
                if ($this->isRequiredAssert($attribute->getName())) {
                    $propertyConstraints[] = $attribute->newInstance();
                }
            }

            if (!$this->checkContainConstraint($propertyConstraints, [Assert\Type::class])) {
                $propertyConstraints[] = new Assert\Type($type->getName());
            }

            if (!$this->checkContainConstraint($propertyConstraints, [Assert\NotBlank::class, Assert\NotNull::class])) {
                $defaultValue = $this->reflectionUtilService->getPropertyDefaultValue($property);

                if (!$type->allowsNull() && $defaultValue === null) {
                    $propertyConstraints[] = new Assert\NotNull();
                }
            }

            if ($propertyConstraints) {
                // Например, у массивов уже есть $constraints[$name] = [new Assert\All()]
                $constraints[$name] = [...$propertyConstraints, ...($constraints[$name] ?? [])];
            }
        }

        foreach ($notExpectedFields as $name => $value) {
            if (!is_array($value)) {
                $constraints[$name] = new NoExtraFields();
            }
        }

        return new ExtendedCollection($constraints, allowExtraFields: true);
    }

    private function getRequestValue(string $name, array $payload, ?RequestValueSource $source, Request $request, ValidRequest $validRequest): mixed
    {
        if (!$source || $source == $validRequest->source) {
            return $payload[$name] ?? null;
        }

        return match ($source) {
            RequestValueSource::attributes => $validRequest->source == RequestValueSource::attributes ? $payload[$name] ?? null : $request->attributes->get($name),
            RequestValueSource::cookies => $request->cookies->get($name),
            RequestValueSource::headers => $request->headers->get($name),
            RequestValueSource::files => $request->files->get($name),
            RequestValueSource::server => $request->server->get($name),
            RequestValueSource::payload => $request->getPayload()->get($name) ?? null,
            RequestValueSource::query => $request->query->get($name),
        };
    }

    private function isRequiredAssert(string $class): bool
    {
        $isNotBlank = is_a($class, Assert\NotBlank::class, true);
        $isNotNull = is_a($class, Assert\NotNull::class, true);
        $isType = is_a($class, Assert\Type::class, true);

        return $isNotNull || $isNotBlank || $isType;
    }

    /**
     * @param array $payload
     * @param ReflectionProperty[] $properties
     * @param ValidRequest $validRequest
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function notExpectedFields(array $payload, array $properties, ValidRequest $validRequest): array
    {
        return array_diff_key_recursive($payload, $this->getObjectStruct($properties, $validRequest));
    }

    private function checkContainConstraint(array $constraints, array $checkedConstraints): bool
    {
        foreach ($checkedConstraints as $checkedConstraint) {
            if (array_find($constraints, fn(Constraint $constraint): bool => is_a($constraint, $checkedConstraint))) {
                return true;
            }
        }

        return false;
    }
}

function array_diff_key_recursive(array $array1, array $array2): array
{
    $difference = array_diff_key($array1, $array2);

    foreach ($array1 as $key => $value) {
        if (array_key_exists($key, $array2) && is_array($value)) {
            if (is_array($array2[$key])) {
                $recursiveDiff = array_diff_key_recursive($value, $array2[$key]);

                if (!empty($recursiveDiff)) {
                    $difference[$key] = $recursiveDiff;
                }
            } else {
                $difference[$key] = $value;
            }
        }
    }

    return $difference;
}
