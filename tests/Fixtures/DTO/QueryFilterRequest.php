<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest(source: RequestValueSource::query)]
readonly class QueryFilterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $page,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $limit,
    ) {}
}
