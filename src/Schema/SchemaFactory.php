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
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class SchemaFactory
{
    /** @var array<class-string, ObjectSchema> */
    private array $memory = [];

    public function __construct(
        private readonly ReflectionUtilService $reflectionUtilService,
        private readonly string $cacheDir,
        private readonly bool $debug,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @throws ReflectionException
     * @throws CyclicSchemaException
     */
    public function create(string $className, array $classes = [], array &$files = []): ObjectSchema
    {
        if (isset($this->memory[$className])) {
            return $this->memory[$className];
        }

        $cacheFile = $this->cacheDir . '/' . str_replace('\\', '_', $className) . '.php';
        $cache = new ConfigCache($cacheFile, $this->debug);

        if ($cache->isFresh()) {
            return $this->memory[$className] = unserialize(file_get_contents($cacheFile));
        }

        $schema = $this->build(new ReflectionClass($className), $classes, $files);

        $cache->write(
            serialize($schema),
            array_map(
                fn (string $file): FileResource => new FileResource($file),
                array_values(array_unique($files)),
            ),
        );

        return $this->memory[$className] = $schema;
    }

    /**
     * @param class-string[] $classes Ordered stack of types being built, for cycle detection
     * @param class-string[] $files
     *
     * @throws CyclicSchemaException
     * @throws ReflectionException
     */
    private function build(ReflectionClass $reflectionClass, array $classes, array &$files): ObjectSchema
    {
        $className = $reflectionClass->getName();

        if (in_array($className, $classes, true)) {
            throw new CyclicSchemaException([...$classes, $className]);
        }

        $classes[] = $className;

        if ($file = $reflectionClass->getFileName()) {
            $files[] = $file;
        }

        $nodes = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isVirtual()) {
                continue;
            }

            $type = PropertyType::create($property);

            $nested = null;
            $arrayItem = null;

            if (!$type->builtin) {
                $nested = $this->create($type->name, $classes, $files);
            } elseif ($type->iterable) {
                $itemType = $this->reflectionUtilService->getTypeFromAssertAll($property);

                if (is_array($itemType)) {
                    throw new RuntimeException('Array type is not supported');
                }

                // На null не проверяю, так как null может быть как явно объявленный Assert\Type(null),
                // что является ошибкой, так и признаком полного отсутствия аттрибута Assert\Type в Assert\All

                if (is_string($itemType) && class_exists($itemType)) {
                    $arrayItem = $this->create($itemType, $classes, $files);
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
