<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Сборка через рефлексию. Свойство приватное, нет конструктора и нет сеттера,
 * поэтому единственный способ гидрации — прямая установка через рефлексию.
 */
#[ValidRequest]
class ReflectionRequest
{
    #[BindRequestParam(name: 'login')]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $username;

    public function getUsername(): string
    {
        return $this->username;
    }
}
