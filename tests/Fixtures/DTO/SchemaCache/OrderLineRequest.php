<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\SchemaCache;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Symfony\Component\Validator\Constraints as Assert;

class OrderLineRequest
{
    public function __construct(
        #[BindRequestParam(name: 'sku')]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[A-Z0-9\-]+$/')]
        public string $articleCode,

        #[Assert\Positive]
        public int $quantity,

        #[Assert\PositiveOrZero]
        public float $unitPrice,

        #[Assert\Length(max: 200)]
        public ?string $note = null,
    ) {
    }
}
