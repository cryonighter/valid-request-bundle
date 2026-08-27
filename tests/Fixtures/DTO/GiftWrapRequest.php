<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class GiftWrapRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['paper', 'box', 'ribbon'])]
        public string $style,

        #[Assert\Length(max: 120)]
        public ?string $message = null,
    ) {
    }
}
