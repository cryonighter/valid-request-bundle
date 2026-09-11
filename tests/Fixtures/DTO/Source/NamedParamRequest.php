<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Source;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки BindRequestParam(name: ...) — поле в запросе называется иначе, чем свойство класса.
 */
#[ValidRequest]
readonly class NamedParamRequest
{
    public function __construct(
        #[BindRequestParam(name: 'user_id')]
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $userId,
    ) {}
}
