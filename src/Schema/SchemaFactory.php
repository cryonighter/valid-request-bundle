<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Exception\CyclicSchemaException;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use DateTimeInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class SchemaFactory
{
    /** @var array<class-string, ObjectSchema> */
    private array $memory = [];

    public function __construct(
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

        // Изменения самих классов схемы тоже должно инфалидировать кэш
        $files += [ObjectSchema::file, PropertyNode::file, PropertyType::file, __FILE__];

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

            $nestedSchemas = [];
            $nestedItemSchemas = [];

            $constraints = $this->collectConstraints($property, $type);

            if (($type->isObject || $type->name === 'mixed') && !$type->iterable && !$this->isEmbeddedClass($type->name)) {
                foreach ($constraints as $constraint) {
                    if ($constraint instanceof Assert\Type) {
                        $nestedSchemas[] = $this->createNestedSchemaFromType($constraint, [], $classes, $files);
                    }
                }

                // Если типы через Assert\Type не указаны или покрывают не все кейсы - используем дефорт по типу свойства.
                // Если он уже покрыт через Assert\Type - ничего страшного, продублируем (это проще, чем искать).
                $schemas = [];

                if ($type->name !== 'mixed' && $type->name !== 'object' && $type->name !== stdClass::class) {
                    $schemas[$type->name] = $this->create($type->name, $classes, $files);
                }

                $nestedSchemas[] = new PropertyNestedItem(
                    groupsAll: new ValidationGroups([]),
                    groupsType: new ValidationGroups(['Default']),
                    types: [$type->name],
                    schemas: $schemas,
                );
            } elseif ($type->iterable) {
                // TODO: сюда могут попасть не только коллекции, но и пользовательские объекты, итерирующиеся по собственным полям.
                // Пока игнорируем этот кейс.

                foreach ($constraints as $constraint) {
                    if (!$constraint instanceof Assert\All) {
                        continue;
                    }

                    foreach ($constraint->constraints as $subConstraint) {
                        if ($subConstraint instanceof Assert\Type) {
                            $nestedItemSchemas[] = $this->createNestedSchemaFromType($subConstraint, $constraint->groups, $classes, $files);
                        }
                    }
                }
            }

            $nodes[$property->getName()] = new PropertyNode(
                propertyName: $property->getName(),
                type: $type,
                bindings: $this->collectBindings($property),
                constraints: $constraints,
                nested: $nestedSchemas,
                nestedItems: $nestedItemSchemas,
                defaultValue: $this->getPropertyDefaultValue($property),
            );
        }

        return new ObjectSchema($className, $nodes, ($reflectionClass->isAbstract() || $reflectionClass->isInterface()));
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
    private function collectConstraints(ReflectionProperty $property, PropertyType $type): array
    {
        $constraints = [];

        if ($type->isObject || $type->iterable || $type->name === 'mixed') {
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

    /**
     * @throws ReflectionException
     */
    private function createNestedSchemaFromType(
        Assert\Type $constraint,
        string|GroupSequence|array|null $groupsAll,
        array $classes,
        array &$files
    ): PropertyNestedItem {
        $types = is_array($constraint->type) ? $constraint->type : [$constraint->type];
        $schemas = [];

        foreach ($types as $itemType) {
            if (is_string($itemType) && class_exists($itemType)) {
                $schemas[$itemType] = $this->create($itemType, $classes, $files);
            }
        }

        return new PropertyNestedItem(
            groupsAll: new ValidationGroups($groupsAll),
            groupsType: new ValidationGroups($constraint->groups),
            types: is_array($constraint->type) ? $constraint->type : [$constraint->type],
            schemas: $schemas,
        );
    }

    /**
     * @param class-string $className
     */
    private function isEmbeddedClass(string $className): bool
    {
        return enum_exists($className) || is_subclass_of($className, DateTimeInterface::class);
    }

    private function getPropertyDefaultValue(ReflectionProperty $property): mixed
    {
        foreach ($property->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $param) {
            // Конечно, имя параметра конструктора не всегда равно имени свойства класса,
            // но это распространенная практика, особенно при описании DTO классов.
            // Если они отличаются - то значения по умолчанию не будет и валидатор затребует передать его явно.
            // Если параметр совпадает с одним свойством по имени, но фактически присваевается другому - будет коллизия,
            // решить которую эвристически не получится, да это и не особо нужно, потому что пользователь явно делает что-то странное.
            if ($param->getName() === $property->getName()) {
                return $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }

        return $property->hasDefaultValue() ? $property->getDefaultValue() : null;
    }
}
