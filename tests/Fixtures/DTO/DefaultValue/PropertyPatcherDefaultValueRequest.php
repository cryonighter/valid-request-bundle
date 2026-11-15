<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\RequestIdPatcher;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки того, как патчер свойства влияет
 * на получение null nullable-аргументом контроллера.
 *
 * Правила:
 * - Если патчер свойства вернул null, это не считается вводом.
 * - Если патчер свойства вернул не null, это ввод: данные валидируются.
 */
#[ValidRequest]
readonly class PropertyPatcherDefaultValueRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        public string $role = 'user',

        #[BindRequestParam(patcher: RequestIdPatcher::class)]
        public ?string $requestId = null,
    ) {}
}
