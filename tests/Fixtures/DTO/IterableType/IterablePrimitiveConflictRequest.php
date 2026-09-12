<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * iterable-тип свойства + примитивный Assert\Type.
 *
 * В отличие от объектного типа, array не является «неинстанцируемым»:
 * тут проверяем, что победит — примитивная валидация Assert\Type('int')
 * или структурная логика сборки.
 */
#[ValidRequest]
class IterablePrimitiveConflictRequest
{
    public function __construct(
        #[Assert\Type('int')]
        public iterable $value,
    ) {
    }
}
