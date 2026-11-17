<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Expression;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки групп валидации, вычисляемых выражениями.
 *
 * Поле email валидируется только в группе Default.
 * Поле phone валидируется только в группе Extra.
 * Поля необязательны для сборки объекта (по умолчанию null), чтобы пропуск поля,
 * не входящего в активную группу, не мешал гидрации.
 */
#[ValidRequest]
readonly class ExpressionRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public ?string $email = null,

        #[Assert\NotBlank(groups: ['Extra'])]
        #[Assert\Regex(pattern: '/^\+\d+$/', groups: ['Extra'])]
        public ?string $phone = null,

        #[Assert\NotBlank(groups: ['Default', 'Extra'])]
        public ?string $name = null,
    ) {
    }
}
