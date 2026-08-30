<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\Request;

final class FormProtocolDetector implements TextProtocolDetector
{
    public function isStringProtocol(RequestValueSource $source, Request $request): bool
    {
        return $source !== RequestValueSource::payload || $request->getContentTypeFormat() === 'form';
    }
}
