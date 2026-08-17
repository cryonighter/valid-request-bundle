<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки смешивания источников данных внутри одного объекта.
 * id берётся из атрибутов роута, а name — из тела запроса (payload).
 */
#[ValidRequest]
readonly class MixedSourceRequest
{
    public function __construct(
        #[BindRequestParam(source: RequestValueSource::attributes)]
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $id,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2)]
        public string $name,
    ) {}
}
