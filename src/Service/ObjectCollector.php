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

readonly class ObjectCollector
{
    public function __construct(
        private ReflectionUtilService $reflectionUtilService,
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function collect(ReflectionClass $reflectionClass, array $data, ValidRequest $validRequest): object
    {
        $className = $reflectionClass->getName();

        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor?->getParameters();

        if ($constructor && $parameters) {
            return new $className(...$this->getConstructorArguments($data, $parameters, $validRequest));
        }

        return new $className();
    }

    /**
     * @param array $payload
     * @param ReflectionParameter[] $parameters
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
            $type = $parameter->getType();

            $value = $payload[$name] ?? null;

            if ($value === null) {
                if ($type->allowsNull()) {
                    $arguments[] = null;

                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();

                    continue;
                }

                if ($type->getName() == 'array') {
                    $arguments[] = [];

                    continue;
                }
            }

            if ($type && !$type->isBuiltin() && is_array($value)) {
                $arguments[] = $this->collect(new ReflectionClass($type->getName()), $value, $validRequest);
            } elseif ($type && $type->getName() == 'array') {
                $typeFromAssertAll = $this->reflectionUtilService->getTypeFromAssertAll($parameter);

                // TODO: а еще $typeFromAssertAll может быть массивом классов
                if (is_string($typeFromAssertAll) && class_exists($typeFromAssertAll)) {
                    $arguments[] = array_map(
                        fn(array $item): object => $this->collect(new ReflectionClass($typeFromAssertAll), $item, $validRequest),
                        $value,
                    );
                } else {
                    $arguments[] = $value ?? throw new RuntimeException("The '$name' property must be non-empty, nullable, or have a default value");
                }
            } elseif (!$type) {
                // TODO: если у аргумента нет тайпхинта пока считаем его примитивом, но по хорошему надо резольвить
                $arguments[] = $value ?? throw new RuntimeException("The '$name' property must be non-empty, nullable, or have a default value");
            } else {
                $arguments[] = $value ?? throw new RuntimeException("The '$name' property must be non-empty, nullable, or have a default value");
            }
        }

        return $arguments;
    }

    public function getFieldName(ReflectionProperty|ReflectionParameter $item, ValidRequest $validRequest): string
    {
        return $this->getBindRequestParamByGroups($item, $validRequest)?->name ?: $item->getName();
    }

    private function getBindRequestParamByGroups(ReflectionProperty|ReflectionParameter $item, ValidRequest $validRequest): ?BindRequestParam
    {
        return array_find(
            array_map(
                fn(ReflectionAttribute $attribute): BindRequestParam => $attribute->newInstance(),
                $item->getAttributes(BindRequestParam::class),
            ),
            fn(BindRequestParam $bindPayload): bool => $validRequest->validationGroups->intersects($bindPayload->validationGroups),
        );
    }
}
