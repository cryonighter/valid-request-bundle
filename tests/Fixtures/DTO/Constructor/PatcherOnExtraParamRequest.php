<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\NowDateTimePatcher;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Параметр $createdAt не соответствует ни одному свойству, не имеет
 * значения по умолчанию и не nullable, но его значение предоставляется
 * патчером через BindRequestParam(patcher: ...) — только патчер, без
 * имени и источника. Объект должен собраться.
 */
#[ValidRequest]
class PatcherOnExtraParamRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(
        string $title,

        #[BindRequestParam(patcher: NowDateTimePatcher::class)]
        DateTime $createdAt,
    ) {
        $this->title = $title;
    }
}
