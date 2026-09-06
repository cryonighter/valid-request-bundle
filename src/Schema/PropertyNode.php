<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Symfony\Component\Validator\Constraint;

readonly class PropertyNode
{
    public const string file = __FILE__;

    /**
     * @param BindRequestParam[]   $bindings    All repeatable #[BindRequestParam] of the property
     * @param Constraint[]         $constraints Constraints of the properties required for hydration of an object.
     * @param ObjectSchema|null    $nested      Schema of a nested object (always present when type is non-builtin class)
     * @param PropertyNestedItem[] $nestedItems  Schemas ofa nested iterable element type resolved from Assert\All + Assert\Type and gruped by validation groups
     */
    public function __construct(
        public string $propertyName,
        public PropertyType $type,
        public array $bindings,
        public array $constraints,
        public ?ObjectSchema $nested = null,
        public array $nestedItems = [],
        public bool $hasAssertValid = false,
        public mixed $defaultValue = null,
    ) {
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
