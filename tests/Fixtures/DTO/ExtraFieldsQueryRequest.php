<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки того, что лишние поля в источнике query
 * не вызывают violation — бандл проверяет лишние поля только для payload.
 */
#[ValidRequest(source: RequestValueSource::query)]
readonly class ExtraFieldsQueryRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $page,
    ) {}
}
