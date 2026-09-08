<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тайпхинт — интерфейс PaymentMethodInterface, но Assert\Type задаёт примитивный тип int.
 *
 * Ожидается ValidRequestException: переопределить объектный тайпхинт примитивом нельзя.
 */
#[ValidRequest]
class PrimitiveInTypeRequest
{
    public function __construct(
        #[Assert\Type(type: 'int')]
        #[Assert\Valid]
        public PaymentMethodInterface $method,
    ) {}
}
