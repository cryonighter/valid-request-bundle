<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Сборка через конструктор. Имя параметра запроса ('login') отличается
 * и от имени свойства класса ($username), и от имени параметра конструктора ($email).
 * Поэтому #[BindRequestParam(name: 'login')] указан и на свойстве, и на параметре конструктора.
 */
#[ValidRequest]
class ConstructorRequest
{
    #[BindRequestParam(name: 'login')]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $username;

    public function __construct(
        #[BindRequestParam(name: 'login')]
        string $email,
    ) {
        $this->username = $email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
