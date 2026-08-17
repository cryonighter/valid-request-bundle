<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Патчер для автоматической генерации slug из поля title,
 * если пользователь не передал slug явно.
 */
class SlugPatcher implements PatcherInterface
{
    public function patch(mixed $value, Request $request): mixed
    {
        if ($value) {
            return $value;
        }

        return strtolower(
            preg_replace(
                ['/[^a-zA-Z0-9\s-]/', '/\s+/', '/-+/'],
                ['', '-', '-'],
                trim($request->request->get('title', '')),
            ),
        );
    }
}
