<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use BackedEnum;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;
use Traversable;

readonly class PropertyType
{
    public const string file = __FILE__;

    public bool $iterable;
    public bool $isEnum;

    public function __construct(
        public string $name,
        public bool $builtin,
        public bool $nullable,
    ) {
        $this->iterable = $this->isIterable();
        $this->isEnum = !$builtin && is_subclass_of($name, BackedEnum::class);
    }

    public static function create(ReflectionProperty $property): self
    {
        $type = $property->getType();

        if (null === $type) {
            throw new RuntimeException('Properties with undeclared type are not supported');
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            throw new RuntimeException('Union and intersection types are not supported');
        }

        // На будущее, вдруг появится 4й тип
        if (!$type instanceof ReflectionNamedType) {
            throw new RuntimeException('Only named types are supported');
        }

        return new self($type->getName(), $type->isBuiltin(), $type->allowsNull());
    }

    private function isIterable(): bool
    {
        if (in_array($this->name, ['array', 'iterable'])) {
            return true;
        }

        if (!$this->builtin && (class_exists($this->name) || interface_exists($this->name))) {
            return is_subclass_of($this->name, Traversable::class);
        }

        return false;
    }
}
