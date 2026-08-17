<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest(source: RequestValueSource::attributes)]
readonly class AttributeRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $id,
    ) {}
}
