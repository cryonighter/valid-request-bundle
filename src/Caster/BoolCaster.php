<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Schema\PropertyType;

final class BoolCaster implements ValueCasterInterface
{
    public function supports(PropertyType $type): bool
    {
        return $type->name === 'bool';
    }

    public function cast(mixed $value, CastContext $context): CastResult
    {
        if (!$context->isTextProtocol || !is_string($value) || trim($value) !== $value) {
            return CastResult::skip();
        }

        if (in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true)) {
            return CastResult::converted(true);
        }

        if (in_array(strtolower($value), ['0', 'false', 'off', 'no'], true)) {
            return CastResult::converted(false);
        }

        return CastResult::skip();
    }
}
