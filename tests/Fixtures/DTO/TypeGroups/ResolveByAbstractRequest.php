<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тип свойства — абстрактный класс, конкретный класс задан через Assert\Type.
 */
#[ValidRequest]
class ResolveByAbstractRequest
{
    public function __construct(
        #[Assert\Type(type: CryptoPayment::class)]
        #[Assert\Valid]
        public AbstractPaymentMethod $method,
    ) {}
}
