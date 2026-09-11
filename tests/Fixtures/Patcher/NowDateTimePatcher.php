<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

/**
 * Безусловно возвращает текущий момент как DateTime.
 */
class NowDateTimePatcher implements PatcherInterface
{
    public function patch(mixed $value, Request $request): DateTime
    {
        return new DateTime('now');
    }
}
