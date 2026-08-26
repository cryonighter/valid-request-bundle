<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Symfony\Component\Validator\Constraint;

readonly class PropertyNode
{
    /**
     * @param BindRequestParam[]  $bindings    All repeatable #[BindRequestParam] of the property (group-agnostic)
     * @param Constraint[]        $constraints
     * @param ObjectSchema|null   $nested      Schema of a nested object (always present when type is non-builtin class)
     * @param ObjectSchema|null   $arrayItem   Schema of array element type resolved from Assert\All + Assert\Type
     */
    public function __construct(
        public string $propertyName,
        public PropertyType $type,
        public array $bindings,
        public array $constraints,
        public ?ObjectSchema $nested = null,
        public ?ObjectSchema $arrayItem = null,
        public bool $hasAssertValid = false,
        public bool $hasDefault = false,
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
}
