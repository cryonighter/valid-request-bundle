<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Groups;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class MultiBindRequest
{
    public function __construct(
        #[BindRequestParam(name: 'access_token', source: RequestValueSource::query, groups: ['Web'])]
        #[BindRequestParam(name: 'X-Api-Token', source: RequestValueSource::headers, groups: ['Api'])]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $token,
    ) {
    }
}
