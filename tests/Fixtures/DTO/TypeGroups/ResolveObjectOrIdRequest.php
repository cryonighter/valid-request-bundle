<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойство mixed с двумя Assert\Type по группам:
 * для группы Full — объект CardPayment
 * для группы Ref — примитивный идентификатор (digit)
 */
#[ValidRequest]
class ResolveObjectOrIdRequest
{
    public function __construct(
        #[Assert\Type(type: CardPayment::class, groups: ['Full'])]
        #[Assert\Type(type: 'digit', groups: ['Ref'])]
        #[Assert\Positive(groups: ['Ref'])]
        #[Assert\Valid(groups: ['Full'])]
        public mixed $method,
    ) {}
}
