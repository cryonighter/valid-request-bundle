<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Сеттер существует, но он приватный. Контракт требует публичный сеттер,
 * поэтому эта стратегия отвергается и гидрация уходит в рефлексию.
 */
#[ValidRequest]
class PrivateSetterRequest
{
    #[BindRequestParam(name: 'login')]
    private string $username;

    private function setUsername(string $email): void
    {
        $this->username = $email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
