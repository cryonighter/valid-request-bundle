<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Сеттер публичный, но принимает два аргумента. Контракт требует ровно один,
 * поэтому эта стратегия отвергается и гидрация уходит в рефлексию.
 */
#[ValidRequest]
class TwoArgsSetterRequest
{
    #[BindRequestParam(name: 'login')]
    private string $username;

    public function setUsername(string $email, string $extra): void
    {
        $this->username = $email . $extra;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
