<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CountryRequest
{
    public function __construct(
        #[BindRequestParam(source: RequestValueSource::attributes, groups: ['Update'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $code,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name,
    ) {}
}
