<?php

namespace Cryonighter\ValidRequestBundle\Caster;

final readonly class CastResult
{
    private function __construct(
        public bool $converted,
        public mixed $value,
    ) {
    }

    public static function converted(mixed $value): self
    {
        return new self(true, $value);
    }

    public static function skip(): self
    {
        return new self(false, null);
    }
}
