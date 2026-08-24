<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Validator\ExtendedCollection;

readonly class ObjectSchema
{
    /**
     * @param class-string   $className
     * @param PropertyNode[] $properties Indexed by property name (virtual properties excluded)
     */
    public function __construct(
        public string $className,
        public array $properties,
    ) {
    }

    /**
     * Group-agnostic collection keyed by PROPERTY name.
     * The consumer re-keys it to field names (per request groups) and applies NoExtraFields.
     */
    public function toCollection(): ExtendedCollection
    {
        $constraints = [];

        foreach ($this->properties as $node) {
            $constraints[$node->propertyName] = $node->constraints;
        }

        return new ExtendedCollection($constraints, allowExtraFields: true);
    }
}
