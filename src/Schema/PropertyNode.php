<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\UnbindRequestParam;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

readonly final class PropertyNode
{
    public const string file = __FILE__;

    /**
     * @param BindRequestParam[]   $bindings    All repeatable #[BindRequestParam] of the property
     * @param Constraint[]         $constraints Constraints of the properties required for hydration of an object.
     * @param PropertyNestedItem[] $nested      Schemas of a nested object type resolved from Assert\Type and gruped by validation groups
     * @param PropertyNestedItem[] $nestedItems Schemas of a nested iterable element type resolved from Assert\All + Assert\Type and gruped by validation groups
     */
    public function __construct(
        public string $propertyName,
        public PropertyType $type,
        public array $bindings,
        public array $unbindings,
        public array $constraints,
        public array $nested = [],
        public array $nestedItems = [],
        public mixed $defaultValue = null,
    ) {
    }

    public function resolveUnbinding(ValidationGroups $groups): ?UnbindRequestParam
    {
        return array_find(
            $this->unbindings,
            fn (UnbindRequestParam $unbinding): bool => $groups->intersects($unbinding->validationGroups),
        );
    }

    /**
     * Group-aware selection of the binding. The ONLY place where groups matter.
     */
    public function resolveBinding(ValidationGroups $groups): ?BindRequestParam
    {
        return array_find(
            $this->bindings,
            fn (BindRequestParam $binding): bool => $groups->intersects($binding->validationGroups),
        );
    }

    public function fieldName(ValidationGroups $groups): string
    {
        return $this->resolveBinding($groups)?->name ?: $this->propertyName;
    }

    public function hasValidConstraint(ValidationGroups $requestGroups): bool
    {
        return array_any(
            array_filter(
                $this->constraints,
                fn(Constraint $constraint): bool => $constraint instanceof Assert\Valid,
            ),
            fn(Assert\Valid $constraint): bool => $requestGroups->intersects(new ValidationGroups($constraint->groups)),
        );
    }

    public function getNestedTypes(ValidationGroups $requestGroups): array
    {
        foreach ($this->nested as $item) {
            if ($requestGroups->intersects($item->groupsType)) {
                return $item->types;
            }
        }

        return [];
    }

    public function getNestedSchema(ValidationGroups $requestGroups): ?ObjectSchema
    {
        foreach ($this->nested as $item) {
            if ($requestGroups->intersects($item->groupsType) && $item->schemas) {
                return array_values($item->schemas)[0];
            }
        }

        return null;
    }

    public function getItemSchema(ValidationGroups $requestGroups): ?ObjectSchema
    {
        foreach ($this->nestedItems as $item) {
            if ($requestGroups->intersects($item->groupsAll) && $requestGroups->intersects($item->groupsType) && $item->schemas) {
                return array_values($item->schemas)[0];
            }
        }

        return null;
    }

    public function getItemSchemaByClassName(ValidationGroups $requestGroups, string $className): ?ObjectSchema
    {
        foreach ($this->nestedItems as $item) {
            if ($requestGroups->intersects($item->groupsAll) && $requestGroups->intersects($item->groupsType) && isset($item->schemas[$className])) {
                return $item->schemas[$className];
            }
        }

        return null;
    }
}
