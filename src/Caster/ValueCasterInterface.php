<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Schema\PropertyType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('cryonighter.valid_request.value_caster')]
interface ValueCasterInterface
{
    /**
     * Whether this caster handles the given property type.
     * This is about the target TYPE only — request conditions are decided in cast().
     */
    public function supports(PropertyType $type): bool;

    /**
     * Attempts to cast a raw value using the given context.
     * Returns CastResult::skip() to leave the raw value for further handling.
     */
    public function cast(mixed $value, CastContext $context): CastResult;
}
