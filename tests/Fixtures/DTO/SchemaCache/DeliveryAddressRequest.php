<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\SchemaCache;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\AcceptLanguagePatcher;
use Symfony\Component\Validator\Constraints as Assert;

class DeliveryAddressRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $country,

        #[Assert\NotBlank]
        public string $city,

        #[BindRequestParam(name: 'zip')]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{5,6}$/')]
        public string $postalCode,

        #[Assert\NotBlank]
        public string $street,

        // язык интерфейса берём из заголовков
        #[BindRequestParam(name: 'Accept-Language', source: RequestValueSource::headers, patcher: AcceptLanguagePatcher::class)]
        #[Assert\Language]
        public ?string $preferredLanguage = null,
    ) {
    }
}
