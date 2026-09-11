<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Вложенные структуры внутри object-свойства:
 * ассоциативный массив → вложенный stdClass, индексный массив → остаётся массивом.
 */
#[ValidRequest]
class NestedObjectRequest
{
    public function __construct(
        public object $data,
    ) {
    }
}
