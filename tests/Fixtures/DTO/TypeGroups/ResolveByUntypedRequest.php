<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойство без типа (приравнивается к mixed), конкретный класс задан через Assert\Type.
 * Нетипизированное свойство нельзя объявить через promoted-параметр конструктора,
 * поэтому объявляем его просто как публичное свойство.
 */
#[ValidRequest]
class ResolveByUntypedRequest
{
    #[Assert\Type(type: CardPayment::class)]
    #[Assert\Valid]
    public $method;
}
