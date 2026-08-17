<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO только с float-полем — для изолированного тестирования
 * разных форматов записи дробных чисел (разделитель целой и дробной части).
 */
#[ValidRequest]
readonly class TypeCastFloatRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public float $price,
    ) {}
}
