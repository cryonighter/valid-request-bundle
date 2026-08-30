<?php

namespace Cryonighter\ValidRequestBundle\Caster;

use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Schema\PropertyType;
use Symfony\Component\HttpFoundation\Request;

final readonly class CastContext
{
    public function __construct(
        public PropertyType $type,
        public RequestValueSource $source,
        public Request $request,
        public bool $isTextProtocol,
    ) {
    }
}
