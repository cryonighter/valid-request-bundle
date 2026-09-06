<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups;

use Symfony\Component\Validator\Constraints as Assert;

readonly class Wizard
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Range(min: 1, max: 100)]
        public int $spellPower,
    ) {}
}
