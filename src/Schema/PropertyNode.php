<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Cryonighter\ValidRequestBundle\Validator\ExtendedCollection;
use ReflectionType;

readonly class PropertyNode
{
    /**
     * @param BindRequestParam[]         $bindings    All repeatable #[BindRequestParam] of the property (group-agnostic)
     * @param array|ExtendedCollection   $constraints Same shape the original built into $constraints[$name]:
     *                                                either a list of Constraint, or an ExtendedCollection for
     *                                                #[Assert\Valid] nested objects. Keyed by PROPERTY name upstream;
     *                                                re-keyed to field name by the consumer.
     * @param ObjectSchema|null          $nested      Schema of a nested object (always present when type is non-builtin class)
     * @param ObjectSchema|null          $arrayItem   Schema of array element type resolved from Assert\All + Assert\Type
     */
    public function __construct(
        public string $propertyName,
        public ReflectionType $type,
        public array $bindings,
        public array|ExtendedCollection $constraints,
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
