<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Патчер уровня объекта: если поле createdAt не передано в запросе,
 * подставляет фиксированную дату для предсказуемости тестов.
 */
class TimestampPatcher implements PatcherInterface
{
    public const string DEFAULT_TIMESTAMP = '2024-01-01';

    public function patch(mixed $value, Request $request): mixed
    {
        if (is_array($value) && empty($value['createdAt'])) {
            $value['createdAt'] = self::DEFAULT_TIMESTAMP;
        }

        return $value;
    }
}
