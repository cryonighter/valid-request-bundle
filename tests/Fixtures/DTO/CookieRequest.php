<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest(source: RequestValueSource::cookies)]
readonly class CookieRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 10)]
        public string $session,
    ) {}
}
