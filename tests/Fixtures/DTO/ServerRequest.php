<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest(source: RequestValueSource::server)]
readonly class ServerRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $serverUser,
    ) {}
}
