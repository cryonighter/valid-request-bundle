<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups;

use Symfony\Component\Validator\Constraints as Assert;

readonly class Warrior
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $strength,
    ) {}
}
