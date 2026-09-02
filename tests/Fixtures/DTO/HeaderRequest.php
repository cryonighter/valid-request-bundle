<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest(source: RequestValueSource::headers)]
readonly class HeaderRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $xApiToken,
    ) {}
}
