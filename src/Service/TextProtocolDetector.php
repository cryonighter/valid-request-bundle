<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\Request;

interface TextProtocolDetector
{
    /**
     * Whether values from the given source arrive as plain strings
     * (form-urlencoded, multipart, non-payload sources), so that
     * type normalization from string is applicable.
     */
    public function isStringProtocol(RequestValueSource $source, Request $request): bool;
}
