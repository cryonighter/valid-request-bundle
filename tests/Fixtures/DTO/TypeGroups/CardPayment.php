<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Symfony\Component\Validator\Constraints as Assert;

class CardPayment extends AbstractPaymentMethod
{
    public function __construct(
        string $currency,

        #[Assert\NotBlank]
        #[Assert\Luhn]
        public string $cardNumber,
    ) {
        parent::__construct($currency);
    }

    public function getLabel(): string
    {
        return 'card';
    }
}
