<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Caster\ValueCastComposite;
use Cryonighter\ValidRequestBundle\Caster\CastContext;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Patcher\PatcherLocator;
use Cryonighter\ValidRequestBundle\Payload\Payload;
use Cryonighter\ValidRequestBundle\Schema\ObjectSchema;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use ReflectionException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly final class PayloadExtractor
{
    public function __construct(
        private ConstraintBuilder $constraintBuilder,
        private PatcherLocator $patcherLocator,
        private TextProtocolDetector $stringProtocolDetector,
        private ValidatorInterface $validator,
        private ViolationTransformer $violationTransformer,
        private ValueCastComposite $valueCastComposite,
    ) {
    }

    /**
     * @throws ReflectionException
     * @throws ValidationFailedException
     */
    public function extractValidPayload(
        ObjectSchema $schema,
        ValidRequest $validRequestAttribute,
        Request $request,
        ValidationGroups $requestGroups,
        bool $allowedNull,
    ): ?array {
        $payload = Payload::createFromRequest($request, $validRequestAttribute);

        // Признак того, что хоть какие-то данные пришли из реальных источников,
        // а не были подставлены из значений по умолчанию
        $hasInput = false;

        $data = $this->preparePayloadData($payload, $schema, $hasInput);

        if ($validRequestAttribute->patcher) {
            $patchedData = $this->patcherLocator->get($validRequestAttribute->patcher)->patch($data, $request);

            // Патчер тоже считаем источником входящих данных
            if ($patchedData !== $data) {
                $hasInput = true;
            }

            $data = $patchedData;
        }


        // Решение о null принимается по факту наличия входных данных, а не по содержимому $data:
        // в $data уже подмешаны значения по умолчанию, и по нему нельзя отличить
        // "ничего не прислали" от "прислали значения, совпадающие с дефолтами"
        if ($payload->empty() && !$hasInput && $allowedNull) {
            return null;
        }

        $schemaObjectStruct = $schema->getObjectStruct($requestGroups);

        $notExpectedFields = $validRequestAttribute->source == RequestValueSource::payload ?
            $this->notExpectedFields($payload->toArray(), $schemaObjectStruct) : [];

        $constraints = $this->constraintBuilder->build($schema, $requestGroups, $notExpectedFields);

        $violations = $this->violationTransformer->arrayKeyToObjectKey(
            $this->validator->validate($data, $constraints, $requestGroups->groups),
            $schemaObjectStruct,
        );

        if ($violations->count() != 0) {
            throw new ValidationFailedException($data, $violations);
        }

        return $data;
    }

    /**
     * @throws ReflectionException
     */
    private function preparePayloadData(Payload $payload, ObjectSchema $schema, bool &$hasInput = false): array
    {
        $data = [];

        $request = $payload->request;
        $validRequest = $payload->validRequest;

        $groups = $validRequest->validationGroups;

        foreach ($schema->properties as $node) {
            $bindRequestParam = $node->resolveBinding($groups);

            $source = $bindRequestParam?->source ?? $validRequest->source;
            $name = $bindRequestParam?->name ?: $node->propertyName;
            $type = $node->type;

            $hasValue = $payload->hasValue($name, $source);
            $value = $payload->getValue($name, $source);

            if ($hasValue) {
                $hasInput = true;
            }

            if ($bindRequestParam?->patcher) {
                $patchedValue = $this->patcherLocator->get($bindRequestParam->patcher)->patch($value, $request);

                if ($patchedValue !== null) {
                    $hasInput = true;
                }

                $data[$name] = $patchedValue;

                continue;
            }

            if ($value instanceof UploadedFile) {
                $data[$name] = $value;

                continue;
            }

            $isTextProtocol = $this->stringProtocolDetector->isStringProtocol($source, $request);
            $castContext = new CastContext($type, $source, $request, $isTextProtocol);

            $castResult = $this->valueCastComposite->cast($value, $castContext);

            if ($castResult->converted) {
                $data[$name] = $castResult->value;

                continue;
            }

            $hasAssertValid = $node->hasValidConstraint($groups);

            // Если это невалидируемый объект или nullable объект - то внутреннюю структуру инициализировать не нужно
            if ($value === null && $type->isObject && ($type->nullable || !$hasAssertValid)) {
                $data[$name] = null;

                continue;
            }

            // Если это валидируемый not nullable объект и данные являются массивом или отсутствуют - инициализируем внутреннюю структуру
            if ($type->isObject && $hasAssertValid && (is_array($value) || $value === null)) {
                $data[$name] = $this->preparePayloadData($payload->nested($name), $node->getNestedSchema($groups), $hasInput);

                continue;
            }

            // Если это массив
            if ($type->name == 'array' && is_array($value)) {
                // Если это массив объектов
                $itemSchema = $node->getItemSchema($groups);

                if ($itemSchema !== null) {
                    $data[$name] = array_map(
                        fn(mixed $item): mixed => is_array($item)
                            // Здесь передавать флаг не нужно, так как $value — массив из запроса, то $hasValue уже true
                            ? $this->preparePayloadData(new Payload($item, $request, $validRequest), $itemSchema)
                            : $item, // Если это не массив - оставляем как есть, валидатор проверит тип и выдаст ошибку
                        $value,
                    );

                    continue;
                }
            }

            // Если пустая строка
            if ($value === '' && $isTextProtocol) {
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
        return array_merge($payload->toArray(), $data);
    }

    private function notExpectedFields(array $payload, array $struct): array
    {
        $difference = array_diff_key($payload, $struct);

        foreach ($payload as $key => $value) {
            if (array_key_exists($key, $struct) && is_array($value)) {
                if (is_array($struct[$key])) {
                    $isArrayObjectsStruct = array_is_list($struct[$key]) && count($struct[$key]) === 1;

                    if ($isArrayObjectsStruct) {
                        $struct[$key] = array_combine(
                            array_keys($value),
                            array_fill(0, count($value), $struct[$key][0]),
                        );
                    }

                    $recursiveDiff = $this->notExpectedFields($value, $struct[$key]);

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
}
