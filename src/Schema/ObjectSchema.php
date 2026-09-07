<?php

namespace Cryonighter\ValidRequestBundle\Schema;

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

    public function getObjectStruct(ValidationGroups $groups): array
    {
        $data = [];

        foreach ($this->properties as $node) {
            $name = $node->fieldName($groups);

            if ($node->nested !== null) {
                $data[$name] = $node->nested->getObjectStruct($groups);

                continue;
            }

            $itemSchema = $node->getItemSchema($groups);

            if ($itemSchema) {
                $data[$name] = [$itemSchema->getObjectStruct($groups)];

                continue;
            }

            $data[$name] = $node->type->name;
        }

        return $data;
    }

    public function getObjectMap(ValidationGroups $groups): array
    {
        $data = [];

        foreach ($this->properties as $node) {
            if ($node->nested !== null) {
                $data[$node->propertyName] = $node->nested->getObjectMap($groups);

                continue;
            }

            $itemSchema = $node->getItemSchema($groups);

            if ($itemSchema) {
                $data[$node->propertyName] = [$itemSchema->getObjectMap($groups)];

                continue;
            }

            $data[$node->propertyName] = $node->fieldName($groups);
        }

        return $data;
    }
}
