<?php

namespace Cryonighter\ValidRequestBundle\Schema;

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
}
