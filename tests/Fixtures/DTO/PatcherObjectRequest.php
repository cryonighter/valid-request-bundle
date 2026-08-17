<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\TimestampPatcher;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки патчера на уровне всего объекта (параметр patcher в #[ValidRequest]).
 * Патчер добавляет поле createdAt если оно не передано.
 */
#[ValidRequest(patcher: TimestampPatcher::class)]
readonly class PatcherObjectRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[Assert\NotBlank]
        public string $createdAt,
    ) {}
}
