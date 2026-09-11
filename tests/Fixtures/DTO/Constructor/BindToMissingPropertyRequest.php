<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Параметр $extra ссылается через BindRequestParam на несуществующее
 * свойство/поле — ожидается ValidRequestException.
 */
#[ValidRequest]
class BindToMissingPropertyRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(
        string $title,

        #[BindRequestParam(name: 'nonexistent')]
        string $extra,
    ) {
        $this->title = $title;
    }
}
