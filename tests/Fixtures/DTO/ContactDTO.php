<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Symfony\Component\Validator\Constraints as Assert;

readonly class ContactDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['email', 'phone'])]
        public string $type,

        //#[BindRequestParam(name: 'content')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 100)]
        public string $value,
    ) {}
}
