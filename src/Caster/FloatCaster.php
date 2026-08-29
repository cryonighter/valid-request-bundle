<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Schema\PropertyType;
use Cryonighter\ValidRequestBundle\Utils\NumberFormatParser;

final class FloatCaster implements ValueCasterInterface
{
    public function supports(PropertyType $type): bool
    {
        return $type->name === 'float';
    }

    public function cast(mixed $value, PropertyType $type): CastResult
    {
        $parsed = NumberFormatParser::parseFloat($value);

        return $parsed !== null ? CastResult::converted($parsed) : CastResult::skip();
    }
}
