<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Schema\Exception\CyclicSchemaException;
use Cryonighter\ValidRequestBundle\Service\ReflectionUtilService;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
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

            $type = PropertyType::create($property);

            $nested = null;
            $arrayItem = null;

            if (!$type->builtin) {
                $nested = $this->build(new ReflectionClass($type->name), $path);
            } elseif ($type->iterable) {
                $itemType = $this->reflectionUtilService->getTypeFromAssertAll($property);

                if (is_array($itemType)) {
                    throw new RuntimeException('Array type is not supported');
                }

                // На null не проверяю, так как null может быть как явно объявленный Assert\Type(null),
                // что является ошибкой, так и признаком полного отсутствия аттрибута Assert\Type в Assert\All

                if (is_string($itemType) && class_exists($itemType)) {
                    $arrayItem = $this->build(new ReflectionClass($itemType), $path);
                }
            }

            $defaultValue = $this->reflectionUtilService->getPropertyDefaultValue($property);

            $nodes[$property->getName()] = new PropertyNode(
                propertyName: $property->getName(),
                type: $type,
                bindings: $this->collectBindings($property),
                constraints: $this->buildConstraints($property, $type),
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

    /**
     * @return Constraint[]
     */
    private function buildConstraints(ReflectionProperty $property, PropertyType $type): array
    {
        $constraints = [];

        if (!$type->builtin || $type->iterable) {
            foreach ($property->getAttributes(Assert\Valid::class) as $validConstraint) {
                $constraints[] = $validConstraint->newInstance();
            }
        }

        if ($type->iterable) {
            foreach ($property->getAttributes(Assert\All::class) as $allConstraint) {
                $constraints[] = $allConstraint->newInstance();
            }
        }

        $notBlankConstraints = $property->getAttributes(Assert\NotBlank::class);
        $notNullConstraints = $property->getAttributes(Assert\NotNull::class);
        $typeConstraints = $property->getAttributes(Assert\Type::class);

        foreach ([...$notBlankConstraints, ...$notNullConstraints, ...$typeConstraints] as $constraint) {
            $constraints[] = $constraint->newInstance();
        }

        return $constraints;
    }
}
