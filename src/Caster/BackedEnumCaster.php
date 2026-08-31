<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use BackedEnum;
use Cryonighter\ValidRequestBundle\Schema\PropertyType;

class BackedEnumCaster implements ValueCasterInterface
{
    public function supports(PropertyType $type): bool
    {
        return !$type->builtin && is_subclass_of($type->name, BackedEnum::class);
    }

    public function cast(mixed $value, CastContext $context): CastResult
    {
        if (!is_string($value) && !is_int($value)) {
            return CastResult::skip();
        }

        // Сравнегие с trim нестрогое, так как trim приведет int к string
        if ($value === '' || trim($value) != $value) {
            return CastResult::skip();
        }

        $enum = ($context->type->name)::tryFrom($value);

        return $enum !== null ? CastResult::converted($enum) : CastResult::skip();
    }
}
