<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
readonly class AuthRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $login,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
    ) {}
}
