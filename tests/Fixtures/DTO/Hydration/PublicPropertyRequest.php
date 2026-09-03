<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Сборка через публичное свойство. Нет конструктора и нет сеттера,
 * бандл установит значение напрямую в публичное свойство.
 */
#[ValidRequest]
class PublicPropertyRequest
{
    #[BindRequestParam(name: 'login')]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $username;
}
