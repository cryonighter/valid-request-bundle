<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Патчер для извлечения языка из заголовка Accept-Language
 */
class AcceptLanguagePatcher implements PatcherInterface
{
    public function patch(mixed $value, Request $request): mixed
    {
        return $value ? strtolower(substr($value, 0, 2)) : $value;
    }
}
