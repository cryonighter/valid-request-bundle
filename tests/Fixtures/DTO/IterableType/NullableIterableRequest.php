<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Nullable iterable-свойство: при null в запросе или отсутствии поля
 * свойство получает null.
 */
#[ValidRequest]
class NullableIterableRequest
{
    public function __construct(
        public ?iterable $value = null,
    ) {
    }
}
