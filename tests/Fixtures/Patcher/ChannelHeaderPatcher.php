<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Патчер объекта: если есть заголовок X-Channel, записывает его значение в поле channel.
 * Если заголовка нет, возвращает данные без изменений.
 */
class ChannelHeaderPatcher implements PatcherInterface
{
    public function patch(mixed $value, Request $request): mixed
    {
        $channel = $request->headers->get('X-Channel');

        if ($channel === null || !is_array($value)) {
            return $value;
        }

        $value['channel'] = $channel;

        return $value;
    }
}
