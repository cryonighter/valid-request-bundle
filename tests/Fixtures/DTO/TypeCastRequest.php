<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки автоматического преобразования типов из form-данных.
 *
 * Все поля типизированы — бандл должен привести строковые значения
 * из form-запроса к нужным типам до валидации.
 */
#[ValidRequest]
readonly class TypeCastRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('bool')]
        public bool $active,

        #[Assert\NotNull]
        #[Assert\Positive]
        public int $count,

        #[Assert\NotNull]
        #[Assert\Positive]
        public float $price,
    ) {}
}
