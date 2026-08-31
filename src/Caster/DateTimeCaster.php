<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Schema\PropertyType;
use DateTimeInterface;
use Throwable;

class DateTimeCaster implements ValueCasterInterface
{
    public function supports(PropertyType $type): bool
    {
        return !$type->builtin && is_subclass_of($type->name, DateTimeInterface::class);
    }

    public function cast(mixed $value, CastContext $context): CastResult
    {
        try {
            // Numeric может быть string, поэтому его проверяем первым
            if (is_numeric($value)) {
                return CastResult::converted(($context->type->name)::createFromTimestamp($value));
            }

            if (is_string($value) && trim($value) === $value && $value !== '') {
                return CastResult::converted(new ($context->type->name)($value));
            }

            return CastResult::skip();
        } catch (Throwable) {
            return CastResult::skip();
        }
    }
}
