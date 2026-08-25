<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Cryonighter\ValidRequestBundle\Validator\ExtendedCollection;
use Cryonighter\ValidRequestBundle\Validator\NoExtraFields;
use ReflectionProperty;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class ConstraintBuilder
{
    /**
     * Builds the request-time ExtendedCollection from a cached, group-agnostic schema.
     * Replaces PayloadExtractor::getPropertiesAttributes().
     *
     * @param array $notExpectedFields Result of array_diff_key_recursive(), keyed by FIELD name
     */
    public function build(ObjectSchema $schema, ValidationGroups $groups, array $notExpectedFields): ExtendedCollection
    {
        $constraints = [];

        foreach ($schema->properties as $node) {
            // Единственное место, где важны группы — выбор имени поля.
            $name = $node->fieldName($groups);
            $property = $node->reflection;
            $type = $node->type;

            // #[Assert\Valid] объект — под ключ кладётся под-коллекция (в оригинале: рекурсивный вызов).
            if (!$type->isBuiltin() && $property->getAttributes(Assert\Valid::class)) {
                $constraints[$name] = $this->build(
                    $node->nested,
                    $groups,
                    is_array($notExpectedFields[$name] ?? null) ? $notExpectedFields[$name] : [],
                );

                continue;
            }

            if ($type->getName() == 'array' && $property->getAttributes(Assert\All::class)) {
                $typeDefaultMessage = new ReflectionProperty(Assert\Type::class, 'message')->getDefaultValue();
                $constraints[$name] = [];

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
                                // Под-коллекция элемента массива (в оригинале — рекурсивный getPropertiesAttributes).
                                // notExpectedFields для элементов массива в оригинале не считались (передавался []).
                                $allConstraints[] = $this->build($node->arrayItem, $groups, []);
                            } else {
                                $allConstraints[] = $constraint;
                            }
                        }
                    }

                    if (!$allConstraints) {
                        continue;
                    }

                    $constraints[$name][] = new Assert\All($allConstraints);
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
                if (!$type->allowsNull() && $node->defaultValue === null) {
                    $propertyConstraints[] = new Assert\NotNull();
                }
            }

            if ($propertyConstraints) {
                // Например, у массивов уже есть $constraints[$name] = [new Assert\All()]
                $constraints[$name] = [...$propertyConstraints, ...($constraints[$name] ?? [])];
            }
        }

        foreach ($notExpectedFields as $name => $value) {
            if (!is_array($value)) {
                $constraints[$name] = new NoExtraFields();
            }
        }

        return new ExtendedCollection($constraints, allowExtraFields: true);
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
