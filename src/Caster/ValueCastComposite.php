<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Schema\PropertyType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly final class ValueCastComposite implements ValueCasterInterface
{
    /**
     * @param ValueCasterInterface[] $casters
     */
    public function __construct(
        #[AutowireIterator('cryonighter.valid_request.value_caster')]
        private iterable $casters,
    ) {
    }

    public function supports(PropertyType $type): bool
    {
        foreach ($this->casters as $caster) {
            if ($caster->supports($type)) {
                return true;
            }
        }

        return false;
    }

    public function cast(mixed $value, CastContext $context): CastResult
    {
        foreach ($this->casters as $caster) {
            if ($caster->supports($context->type)) {
                $result = $caster->cast($value, $context);

                if ($result->converted) {
                    return $result;
                }
            }
        }

        return CastResult::skip();
    }
}
