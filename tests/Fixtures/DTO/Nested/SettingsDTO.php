<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Nested;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SettingsDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $itemsPerPage,

        #[Assert\NotBlank]
        public string $theme,
    ) {}
}
