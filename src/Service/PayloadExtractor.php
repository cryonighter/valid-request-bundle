<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Caster\ValueCastComposite;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Patcher\PatcherLocator;
use Cryonighter\ValidRequestBundle\Schema\ObjectSchema;
use Cryonighter\ValidRequestBundle\Schema\SchemaFactory;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function Cryonighter\ValidRequestBundle\array_diff_key_recursive;
use function Cryonighter\ValidRequestBundle\camel_case;
use function Cryonighter\ValidRequestBundle\empty_data_recursive;

readonly class PayloadExtractor
{
    public function __construct(
        private ConstraintBuilder $constraintBuilder,
        private PatcherLocator $patcherLocator,
        private SchemaFactory $schemaFactory,
        private ValidatorInterface $validator,
        private ViolationTransformer $violationTransformer,
        private ValueCastComposite $valueCastComposite,
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

        // Это, конечно, не корректно, так как иметь несколько значений для одного имени - нормально для заголовков.
        // Но вся архитектура выстроена вокруг концепции "ключ=>занчение" и я не знаю, как сделать по другому.
        // Порядок передачи заголовков все равно не гарантирован, поэтому беру первый в массиве
        if ($validRequestAttribute->source == RequestValueSource::headers) {
            $payload = array_map(fn(array $value): string => $value[0] ?? '', $payload);
        }

        // Обогощаем payload ключами в camelCase
        if ($validRequestAttribute->source == RequestValueSource::server) {
            foreach ($payload as $key => $value) {
                $payload[camel_case($key)] = $value;
            }
        }

        $schema = $this->schemaFactory->create($reflectionClass->getName());

        $notExpectedFields = $validRequestAttribute->source == RequestValueSource::payload ?
            $this->notExpectedFields($payload, $schema, $validRequestAttribute) : [];

        $data = $this->addMissingFields($payload, $schema, $request, $validRequestAttribute);

        $constraints = $this->constraintBuilder->build(
            $schema,
            $validRequestAttribute->validationGroups,
            $notExpectedFields,
        );

        $groups = $this->resolveGroups($reflectionClass, $validRequestAttribute);

        if ($validRequestAttribute->patcher) {
            $patcher = $this->patcherLocator->get($validRequestAttribute->patcher);

            $data = $patcher->patch($data, $request);
        }

        // Это проверку пришлось перенести на более поздний этап, после формирования данных
        // Иначе при отсутствии данных в основном источнике возвращается null, не смотря на
        // то что они есть в альтернативных, указанных через BindRequestParam или патчер
        if (empty($payload) && empty_data_recursive($data) && $allowedNull) {
            return null;
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
     * @throws ReflectionException
     */
    private function getObjectStruct(ObjectSchema $schema, ValidRequest $validRequest): array
    {
        $data = [];

        foreach ($schema->properties as $node) {
            $name = $node->fieldName($validRequest->validationGroups);

            $data[$name] = $node->nested !== null
                ? $this->getObjectStruct($node->nested, $validRequest)
                : $node->type->name;
        }

        return $data;
    }

    /**
     * @throws ReflectionException
     */
    private function addMissingFields(array $payload, ObjectSchema $schema, Request $request, ValidRequest $validRequest): array
    {
        $data = [];
        $groups = $validRequest->validationGroups;

        foreach ($schema->properties as $node) {
            $bindRequestParam = $node->resolveBinding($groups);

            $source = $bindRequestParam?->source ?? $validRequest->source;
            $name = $bindRequestParam?->name ?: $node->propertyName;
            $type = $node->type;

            $hasValue = $this->hasRequestValue($name, $payload, $source, $request, $validRequest);
            $value = $this->getRequestValue($name, $payload, $source, $request, $validRequest);

            if ($bindRequestParam?->patcher) {
                $data[$name] = $this->patcherLocator->get($bindRequestParam->patcher)->patch($value, $request);

                continue;
            }

            if ($value instanceof UploadedFile) {
                $data[$name] = $value;

                continue;
            }

            $hasAssertValid = $node->hasAssertValid;

            // Если это невалидируемый объект или nullable объект - то внутреннюю структуру инициализировать не нужно
            if ($value === null && !$type->builtin && ($type->nullable || !$hasAssertValid)) {
                $data[$name] = null;

                continue;
            }

            // Если это валидируемый not nullable объект и данные являются массивом или отсутствуют - инициализируем внутреннюю структуру
            if (!$type->builtin && $hasAssertValid && (is_array($value) || $value === null)) {
                $data[$name] = $this->addMissingFields($value ?? [], $node->nested, $request, $validRequest);

                continue;
            }

            // Если это массив
            if ($type->name == 'array' && is_array($value)) {
                // Если это массив объектов
                if ($node->arrayItem !== null) {
                    $data[$name] = array_map(
                        fn(mixed $item): mixed => is_array($item) ? $this->addMissingFields($item, $node->arrayItem, $request, $validRequest) : $item,
                        $value,
                    );

                    continue;
                }
            }

            // TODO: Проверить application/xml и text/xml (ну и multipart/form-data заодно)
            $isStringProtocol = $source != RequestValueSource::payload || $request->getContentTypeFormat() == 'form';

            if (is_string($value) && $isStringProtocol && trim($value) === $value) {
                $result = $this->valueCastComposite->cast($value, $type);

                if ($result->converted) {
                    $data[$name] = $result->value;

                    continue;
                }
            }

            // Если пустая строка
            if ($value === '' && $isStringProtocol) {
                // Если тип позволяет null
                if ($type->nullable) {
                    $data[$name] = null;

                    continue;
                }

                if ($type->name != 'string') {
                    $data[$name] = $node->defaultValue;

                    continue;
                }
            }

            // Если значение не передано вообще - используем значение по умолчанию
            // Испольовать его при $value === null нельзя, null может быть валидным значением
            if (!$hasValue) {
                $data[$name] = $node->defaultValue;

                continue;
            }

            if ($value === null && $type->nullable) {
                $data[$name] = null;

                continue;
            }

            // Во всех остальных случаях
            $data[$name] = $value;
        }

        // Объединение с payload нужно, чтоб добавить поля, не описанные в объекте, но присутствующие в запросе
        return array_merge($payload, $data);
    }

    private function getRequestValue(string $name, array $payload, RequestValueSource $source, Request $request, ValidRequest $validRequest): mixed
    {
        if ($source == $validRequest->source) {
            return $payload[$name] ?? null;
        }

        return match ($source) {
            RequestValueSource::attributes => $request->attributes->get($name),
            RequestValueSource::cookies => $request->cookies->get($name),
            RequestValueSource::headers => $request->headers->get($name),
            RequestValueSource::files => $request->files->get($name),
            RequestValueSource::server => $request->server->get($name),
            RequestValueSource::payload => $request->getPayload()->get($name) ?? null,
            RequestValueSource::query => $request->query->get($name),
        };
    }

    private function hasRequestValue(string $name, array $payload, RequestValueSource $source, Request $request, ValidRequest $validRequest): bool
    {
        if ($source == $validRequest->source) {
            return array_key_exists($name, $payload);
        }

        return match ($source) {
            RequestValueSource::attributes => $request->attributes->has($name),
            RequestValueSource::cookies => $request->cookies->has($name),
            RequestValueSource::headers => $request->headers->has($name),
            RequestValueSource::files => $request->files->has($name),
            RequestValueSource::server => $request->server->has($name),
            RequestValueSource::payload => $request->getPayload()->has($name),
            RequestValueSource::query => $request->query->has($name),
        };
    }

    /**
     * @throws ReflectionException
     */
    private function notExpectedFields(array $payload, ObjectSchema $schema, ValidRequest $validRequest): array
    {
        return array_diff_key_recursive($payload, $this->getObjectStruct($schema, $validRequest));
    }
}
