<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Schema\Exception\CyclicSchemaException;
use Cryonighter\ValidRequestBundle\Service\ReflectionUtilService;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class SchemaFactory
{
    /** @var array<class-string, ObjectSchema> */
    private array $cache = [];

    public function __construct(
        private readonly ReflectionUtilService $reflectionUtilService,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @throws ReflectionException
     * @throws CyclicSchemaException
     */
    public function create(string $className): ObjectSchema
    {
        // Структура класса не зависит от групп и данных запроса, поэтому кэшируется по FQCN.
        return $this->cache[$className] ??= $this->build(new ReflectionClass($className), []);
    }

    /**
     * @param class-string[] $path Ordered stack of types being built, for cycle detection
     *
     * @throws ReflectionException
     * @throws CyclicSchemaException
     */
    private function build(ReflectionClass $reflectionClass, array $path): ObjectSchema
    {
        $className = $reflectionClass->getName();

        if (in_array($className, $path, true)) {
            throw new CyclicSchemaException([...$path, $className]);
        }

        $path[] = $className;

        $nodes = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isVirtual()) {
                continue;
            }

            $type = $property->getType();
            $typeName = $type->getName();

            $nested = null;
            $arrayItem = null;

            if (!$type->isBuiltin()) {
                $nested = $this->build(new ReflectionClass($typeName), $path);
            } elseif ($typeName === 'array') {
                $itemType = $this->reflectionUtilService->getTypeFromAssertAll($property);

                // TODO: а еще $typeFromAssertAll может быть массивом классов
                if (is_string($itemType) && class_exists($itemType)) {
                    $arrayItem = $this->build(new ReflectionClass($itemType), $path);
                }
            }

            $defaultValue = $this->reflectionUtilService->getPropertyDefaultValue($property);

            $nodes[$property->getName()] = new PropertyNode(
                propertyName: $property->getName(),
                type: $type,
                reflection: $property,
                bindings: $this->collectBindings($property),
                nested: $nested,
                arrayItem: $arrayItem,
                hasAssertValid: !empty($property->getAttributes(Assert\Valid::class)),
                hasDefault: $property->hasDefaultValue() || $defaultValue !== null,
                defaultValue: $defaultValue,
            );
        }

        return new ObjectSchema($className, $nodes);
    }

    /**
     * @return BindRequestParam[]
     */
    private function collectBindings(ReflectionProperty $property): array
    {
        return array_map(
            fn (ReflectionAttribute $attribute): BindRequestParam => $attribute->newInstance(),
            $property->getAttributes(BindRequestParam::class),
        );
    }
}
