<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Принимает на вход DateTime и преобразует его в DateTimeImmutable.
 */
class ToImmutableDateTimePatcher implements PatcherInterface
{
    public function patch(mixed $value, Request $request): DateTimeImmutable
    {
        if (!$value instanceof DateTime) {
            throw new InvalidArgumentException('Value must be instance of DateTime');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
