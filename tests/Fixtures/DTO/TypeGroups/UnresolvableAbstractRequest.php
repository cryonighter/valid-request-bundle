<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тайпхинт — абстрактный класс AbstractPaymentMethod.
 * Assert\Type задан только для группы Card, группы Default среди них нет.
 *
 * Если запрос приходит только с группой Default — ни один Assert\Type не активен,
 * а инстанцировать абстрактный класс нельзя. Ожидается ValidRequestException.
 */
#[ValidRequest]
class UnresolvableAbstractRequest
{
    public function __construct(
        #[Assert\Type(type: CardPayment::class, groups: ['Card'])]
        #[Assert\Valid]
        public AbstractPaymentMethod $method,
    ) {}
}
