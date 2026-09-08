<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Symfony\Component\Validator\Constraints as Assert;

class CryptoPayment extends AbstractPaymentMethod
{
    public function __construct(
        string $currency,

        #[Assert\NotBlank]
        #[Assert\Length(min: 26, max: 62)]
        public string $walletAddress,
    ) {
        parent::__construct($currency);
    }

    public function getLabel(): string
    {
        return 'crypto';
    }
}
