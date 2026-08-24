<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Schema\Exception\CyclicSchemaException;
use Cryonighter\ValidRequestBundle\Service\ReflectionUtilService;
use Cryonighter\ValidRequestBundle\Validator\ExtendedCollection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
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
        // Схема структуры класса не зависит от групп, поэтому кэшируется по FQCN.
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
                bindings: $this->collectBindings($property),
                constraints: $this->buildPropertyConstraints($property, $nested, $arrayItem),
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
     * Ported verbatim from PayloadExtractor::getPropertiesAttributes(), for a SINGLE property.
     * The differences from the original:
     *  - returns the value that the original assigned to $constraints[$name] (an array of Constraint,
     *    or an ExtendedCollection for #[Assert\Valid] objects) instead of accumulating a whole collection;
     *  - the key stays at property level (re-keying to field name is the consumer's job);
     *  - nested / array-item recursion reuses the already-built sub-schemas via $nested / $arrayItem.
     *
     * @return Constraint[]|ExtendedCollection
     */
    private function buildPropertyConstraints(
        ReflectionProperty $property,
        ?ObjectSchema $nested,
        ?ObjectSchema $arrayItem,
    ): array|ExtendedCollection {
        $type = $property->getType();

        if (!$type->isBuiltin() && $property->getAttributes(Assert\Valid::class)) {
            // В оригинале сюда клалась ExtendedCollection от рекурсивного getPropertiesAttributes().
            // Теперь переиспользуем уже построенную под-схему вложенного объекта.
            return $nested->toCollection();
        }

        $constraints = [];

        if ($type->getName() == 'array' && $property->getAttributes(Assert\All::class)) {
            $typeDefaultMessage = new ReflectionProperty(Assert\Type::class, 'message')->getDefaultValue();
            $constraints[$property->getName()] = [];

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
                            $allConstraints[] = $arrayItem->toCollection();
                        } else {
                            $allConstraints[] = $constraint;
                        }
                    }
                }

                if (!$allConstraints) {
                    continue;
                }

                $constraints[$property->getName()][] = new Assert\All($allConstraints);
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

        // Например, у массивов уже есть [$name => [new Assert\All()]] в $constraints[$name]
        return [...$propertyConstraints, ...($constraints[$property->getName()] ?? [])];
    }

    private function isRequiredAssert(string $class): bool
    {
        $isNotBlank = is_a($class, Assert\NotBlank::class, true);
        $isNotNull = is_a($class, Assert\NotNull::class, true);
        $isType = is_a($class, Assert\Type::class, true);

        return $isNotNull || $isNotBlank || $isType;
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
