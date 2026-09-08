<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Currency]
        public string $currency,
    ) {}
}
