<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Сборка через публичный сеттер. Свойства нет в конструкторе,
 * поэтому бандл вызовет setUsername() с именем аргумента 'login' из запроса.
 */
#[ValidRequest]
class SetterRequest
{
    #[BindRequestParam(name: 'login')]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $username;

    public function setUsername(string $email): void
    {
        $this->username = $email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
