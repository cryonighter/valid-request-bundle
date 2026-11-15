<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Патчер свойства: если значения в запросе нет, берёт его из заголовка X-Request-Id.
 * Если нет и заголовка, возвращает null.
 */
class RequestIdPatcher implements PatcherInterface
{
    public function patch(mixed $value, Request $request): mixed
    {
        return $value ?? $request->headers->get('X-Request-Id');
    }
}
