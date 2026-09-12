<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Свойство без Assert\Type: целевой тип определяется типом свойства.
 * iterable → приводится к array.
 */
#[ValidRequest]
class PlainIterableRequest
{
    public function __construct(
        public iterable $value,
    ) {
    }
}
