<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тайпхинт — интерфейс PaymentMethodInterface, но Assert\Type целится в
 * абстрактный класс AbstractPaymentMethod, который тоже нельзя инстанцировать.
 *
 * Ожидается ValidRequestException: переопределение типом, который нельзя инстанцировать.
 */
#[ValidRequest]
class AbstractInTypeRequest
{
    public function __construct(
        #[Assert\Type(type: AbstractPaymentMethod::class)]
        #[Assert\Valid]
        public PaymentMethodInterface $method,
    ) {}
}
