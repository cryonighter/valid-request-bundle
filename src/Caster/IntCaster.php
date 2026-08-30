<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Schema\PropertyType;
use Cryonighter\ValidRequestBundle\Utils\NumberFormatParser;

final class IntCaster implements ValueCasterInterface
{
    public function supports(PropertyType $type): bool
    {
        return $type->name === 'int';
    }

    public function cast(mixed $value, CastContext $context): CastResult
    {
        if (!$context->isTextProtocol || !is_string($value) || trim($value) !== $value) {
            return CastResult::skip();
        }

        $parsed = NumberFormatParser::parseInt($value);

        return $parsed !== null ? CastResult::converted($parsed) : CastResult::skip();
    }
}
