<?php

namespace Cryonighter\ValidRequestBundle\Patcher;

use Symfony\Component\HttpFoundation\Request;

interface PatcherInterface
{
    public function patch(mixed $value, Request $request): mixed;
}
