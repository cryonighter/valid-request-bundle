<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Два свойства (first, second) и два параметра конструктора с теми же именами.
 *
 * Для группы Mirror параметр конструктора $second привязывается через
 * BindRequestParam(name: 'first') к полю first — тогда оба свойства получают
 * значение поля first.
 *
 * Без группы Mirror привязка не активна — каждый параметр берёт значение
 * своего одноимённого поля.
 */
#[ValidRequest]
class BindToOtherPropertyRequest
{
    #[Assert\NotBlank]
    public string $first;

    #[Assert\NotBlank]
    public string $second;

    public function __construct(
        string $first,

        #[BindRequestParam(name: 'first', groups: ['Mirror'])]
        string $second,
    ) {
        $this->first = $first;
        $this->second = $second;
    }
}
