<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки групп валидации.
 *
 * Поле email валидируется только в группе Default.
 * Поле phone валидируется только в группе Extra.
 * Поле name валидируется в обеих группах (Default и Extra).
 */
#[ValidRequest]
readonly class GroupedRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank(groups: ['Extra'])]
        #[Assert\Regex(pattern: '/^\+\d+$/', groups: ['Extra'])]
        public string $phone,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, groups: ['Default', 'Extra'])]
        public string $name,
    ) {}
}
