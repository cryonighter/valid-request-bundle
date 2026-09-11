<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeCast;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO только с int-полем — для изолированного тестирования
 * разных форматов записи целых чисел (разделители тысячных).
 */
#[ValidRequest]
readonly class TypeCastIntRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public int $amount,
    ) {}
}
