<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;

readonly class ObjectHydrator
{
    public function __construct(
        private ReflectionUtilService $reflectionUtilService,
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function hydrate(ReflectionClass $reflectionClass, array $data, ValidRequest $validRequest): object
    {
        $className = $reflectionClass->getName();

        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor?->getParameters();

        if ($constructor && $parameters) {
            $arguments = $this->getConstructorArguments($data, $parameters, $validRequest);

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
            $propertyMap[$this->getFieldName($property, $validRequest)] = $property;
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
            $value = $this->hydrateValue($name, $value, $property, $validRequest);

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
     * @param array $payload
     * @param ReflectionProperty[]|ReflectionParameter[] $parameters
     * @param ValidRequest $validRequest
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function getConstructorArguments(array $payload, array $parameters, ValidRequest $validRequest): array
    {
        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $this->getFieldName($parameter, $validRequest);

            $arguments[$name] = $this->hydrateValue($name, $payload[$name] ?? null, $parameter, $validRequest);
        }

        return $arguments;
    }

    /**
     * @throws ReflectionException
     */
    private function hydrateValue(string $name, mixed $value, ReflectionProperty|ReflectionParameter $property, ValidRequest $validRequest): mixed
    {
        $type = $property->getType();

        if ($value === null) {
            if (($type && $type->allowsNull()) || !$type) {
                return null;
            }

            if ($property->isDefaultValueAvailable()) {
                return $property->getDefaultValue();
            }

            if (in_array($type->getName(), ['array', 'callable'])) {
                return [];
            }
        }

        // TODO: если у аргумента нет тайпхинта пока считаем его примитивом, но по хорошему надо резольвить
        if ($type) {
            if (!$type->isBuiltin() && is_array($value)) {
                return $this->hydrate(new ReflectionClass($type->getName()), $value, $validRequest);
            }

            if ($type->getName() === 'array') {
                // TODO: это не будет работать для обычных (не промоутед) параметров, на которые не вешают аннотацию Assert\All
                $typeFromAssertAll = $this->reflectionUtilService->getTypeFromAssertAll($property);

                if (is_array($typeFromAssertAll)) {
                    throw new RuntimeException('Array type is not supported');
                }

                // На null не проверяю, так как null может быть как явно объявленный Assert\Type(null),
                // что является ошибкой, так и признаком полного отсутствия аттрибута Assert\Type в Assert\All

                if (is_string($typeFromAssertAll) && class_exists($typeFromAssertAll)) {
                    return array_map(
                        fn(array $item): object => $this->hydrate(new ReflectionClass($typeFromAssertAll), $item, $validRequest),
                        $value,
                    );
                }
            }
        }

        return $value ?? throw new RuntimeException("The '$name' property must be non-empty or have a default value");
    }

    private function getFieldName(ReflectionProperty|ReflectionParameter $property, ValidRequest $validRequest): string
    {
        return $this->getBindRequestParamByGroups($property, $validRequest)?->name ?: $property->getName();
    }

    private function getBindRequestParamByGroups(ReflectionProperty|ReflectionParameter $property, ValidRequest $validRequest): ?BindRequestParam
    {
        return array_find(
            array_map(
                fn(ReflectionAttribute $attribute): BindRequestParam => $attribute->newInstance(),
                $property->getAttributes(BindRequestParam::class),
            ),
            fn(BindRequestParam $bindPayload): bool => $validRequest->validationGroups->intersects($bindPayload->validationGroups),
        );
    }
}
