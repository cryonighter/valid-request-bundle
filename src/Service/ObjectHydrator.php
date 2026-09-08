<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Schema\ObjectSchema;
use Cryonighter\ValidRequestBundle\Schema\PropertyNode;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;

readonly class ObjectHydrator
{
    /**
     * @throws ReflectionException
     */
    public function hydrate(ObjectSchema $schema, array $data, ValidRequest $validRequest): object
    {
        $className = $schema->className;
        $reflectionClass = new ReflectionClass($className);

        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor?->getParameters();

        if ($constructor && $parameters) {
            $arguments = $this->getConstructorArguments($schema, $data, $parameters, $validRequest);

            $data = array_diff_key($data, $arguments);

            $object = new $className(...array_values($arguments));
        } else {
            $object = new $className();
        }

        if (!$data) {
            return $object;
        }

        $propertyMap = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyMap[$this->getFieldName($property, $validRequest->validationGroups)] = $property;
        }

        foreach ($data as $name => $value) {
            /** @var ReflectionProperty $property */
            $property = $propertyMap[$name] ?? null;

            // Могут попадаться extra fields, которых нет в классе, но есть в запросе
            // Для источников данных, отличных от payload, это обычное дело
            if (!$property) {
                continue;
            }

            $propertyName = $property->getName();
            $propertyNode = $schema->properties[$propertyName];

            $value = $this->hydrateValue($name, $value, $propertyNode, $validRequest);

            $setter = 'set' . ucfirst($propertyName);

            if ($reflectionClass->hasMethod($setter)) {
                $reflectionSetter = $reflectionClass->getMethod($setter);

                if ($reflectionSetter->isPublic() && $reflectionSetter->getNumberOfParameters() === 1) {
                    $object->$setter($value);

                    continue;
                }
            }

            if ($property->isPublic() && !$property->isReadOnly()) {
                $object->$propertyName = $value;

                continue;
            }

            // Если сеттера нет, а свойство приватное или readonly, то используем рефлексию
            $property->setValue($object, $value);
        }

        return $object;
    }

    /**
     * @param ObjectSchema          $schema
     * @param array                 $payload
     * @param ReflectionParameter[] $parameters
     * @param ValidRequest          $validRequest
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function getConstructorArguments(ObjectSchema $schema, array $payload, array $parameters, ValidRequest $validRequest): array
    {
        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $this->getFieldName($parameter, $validRequest->validationGroups);
            $value = $payload[$name] ?? null;

            $propertyNode = $schema->getPropertyByRequestName($name, $validRequest->validationGroups);

            // Параметр конструктора не сопоставляется со свойством класса ни по имени, ни по бинду
            if (!$propertyNode) {
                if ($value === null) {
                    if ($parameter->getType()->allowsNull()) {
                        $arguments[$name] = null;

                        continue;
                    }

                    if ($parameter->isDefaultValueAvailable()) {
                        $arguments[$name] = $parameter->getDefaultValue();
                    }

                    // Наличие этого кейса спорно. Он появился при другой архитектуре и не понятно нужен ли сейчас.
                    if (in_array($parameter->getType()->getName(), ['array', 'callable'])) {
                        return [];
                    }

                    throw new RuntimeException("The '$name' constructor parameter must be non-empty, nullable, or have a default value");
                }

                $arguments[$name] = $value;

                continue;
            }

            $arguments[$name] = $this->hydrateValue($name, $value, $propertyNode, $validRequest);
        }

        return $arguments;
    }

    /**
     * @throws ReflectionException
     */
    private function hydrateValue(string $name, mixed $value, PropertyNode $propertyNode, ValidRequest $validRequest): mixed
    {
        $type = $propertyNode->type;

        if ($value === null) {
            if ($type->nullable) {
                return null;
            }

            if ($propertyNode->defaultValue) {
                return $propertyNode->defaultValue;
            }

            // Наличие этого кейса спорно. Он появился при другой архитектуре и не понятно нужен ли сейчас.
            if (in_array($type->name, ['array', 'callable'])) {
                return [];
            }
        }

        if ($type->isObject || $type->name === 'mixed') {
            $nestedSchema = $propertyNode->getNestedSchema($validRequest->validationGroups);

            // Типы "object" и "mixed" могут как иметь схему и быть объектами, так и не иметь и быть примитивами.
            // Не встроенные типы схему иметь обязаны, если ее по каким то причинам нет - бросаем исключение.
            // Если значение уже является объектом (например, его уже построил кастер) - пропускаем
            if (($nestedSchema || !$type->builtin) && !is_object($value)) {
                // Данные уже провалидированы, так что такое вряд ли может быть. Но на всякий случай добавлю.
                if (!is_array($value)) {
                    throw new RuntimeException("Hydrating an object requires a list of its properties as an array, not a " . gettype($value));
                }

                if (!$nestedSchema) {
                    throw new RuntimeException("Unable to resolve class for param '$name'");
                }

                return $this->hydrate($nestedSchema, $value, $validRequest);
            }
        }

        if (in_array($type->name, ['array', 'iterable']) && !is_object($value)) {
            $itemSchema = $propertyNode->getItemSchema($validRequest->validationGroups);

            if ($itemSchema) {
                return array_map(
                    fn(array $item): object => $this->hydrate($itemSchema, $item, $validRequest),
                    is_array($value) ? $value : iterator_to_array($value),
                );
            }
        }

        // Валидные nullable кейсы к этому моменту уже отработаны
        return $value ?? throw new RuntimeException("The '$propertyNode->propertyName' property must be non-empty, nullable or have a default value");
    }

    private function getFieldName(ReflectionProperty|ReflectionParameter $property, ValidationGroups $requestGroups): string
    {
        return $this->getBindRequestParamByGroups($property, $requestGroups)?->name ?: $property->getName();
    }

    private function getBindRequestParamByGroups(
        ReflectionProperty|ReflectionParameter $property,
        ValidationGroups $requestGroups,
    ): ?BindRequestParam {
        return array_find(
            array_map(
                fn(ReflectionAttribute $attribute): BindRequestParam => $attribute->newInstance(),
                $property->getAttributes(BindRequestParam::class),
            ),
            fn(BindRequestParam $bindPayload): bool => $requestGroups->intersects($bindPayload->validationGroups),
        );
    }
}
