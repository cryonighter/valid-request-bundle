<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeCast;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO только с bool-полем — для изолированного тестирования
 * всех допустимых и недопустимых строковых представлений булева значения.
 */
#[ValidRequest]
readonly class TypeCastBoolRequest
{
    public function __construct(
        #[Assert\NotNull]
        public bool $flag,
    ) {}
}
