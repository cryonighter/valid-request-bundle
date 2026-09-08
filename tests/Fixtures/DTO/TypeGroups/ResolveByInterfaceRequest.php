<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тип свойства — интерфейс, конкретный класс задан через Assert\Type.
 */
#[ValidRequest]
class ResolveByInterfaceRequest
{
    public function __construct(
        #[Assert\Type(type: CardPayment::class)]
        #[Assert\Valid]
        public PaymentMethodInterface $method,
    ) {}
}
