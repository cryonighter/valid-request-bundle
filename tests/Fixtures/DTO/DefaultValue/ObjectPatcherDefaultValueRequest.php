<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\ChannelHeaderPatcher;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки того, как патчер объекта влияет
 * на получение null nullable-аргументом контроллера.
 *
 * Правила:
 * - Если патчер объекта вернул данные без изменений, это не считается вводом.
 * - Если патчер объекта изменил данные, это ввод: данные валидируются.
 */
#[ValidRequest(patcher: ChannelHeaderPatcher::class)]
readonly class ObjectPatcherDefaultValueRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        public string $role = 'user',

        public string $channel = 'web',
    ) {}
}
