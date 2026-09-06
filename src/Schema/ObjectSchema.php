<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;

readonly class ObjectSchema
{
    public const string file = __FILE__;

    /**
     * @param class-string   $className
     * @param PropertyNode[] $properties Indexed by property name (virtual properties excluded)
     */
    public function __construct(
        public string $className,
        public array $properties,
    ) {
    }

    public function getPropertyByRequestName(string $name, ValidationGroups $groups): ?PropertyNode
    {
        return array_find(
            $this->properties,
            fn(PropertyNode $property): bool => $property->fieldName($groups) === $name,
        );
    }

    public function getObjectStruct(ValidRequest $validRequest): array
    {
        $data = [];

        foreach ($this->properties as $node) {
            $name = $node->fieldName($validRequest->validationGroups);

            $data[$name] = $node->nested !== null
                ? $node->nested->getObjectStruct($validRequest)
                : $node->type->name;
        }

        return $data;
    }

    public function getObjectMap(ValidationGroups $groups): array
    {
        $data = [];

        foreach ($this->properties as $node) {
            $data[$node->propertyName] = $node->nested !== null
                ? $node->nested->getObjectMap($groups)
                : $node->fieldName($groups);
        }

        return $data;
    }
}
