<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use stdClass;

/**
 * Nullable object-свойства: при null в запросе
 * или отсутствии поля свойство получает null.
 */
#[ValidRequest]
class NullableObjectRequest
{
    public function __construct(
        public ?object $asObject,
        public ?stdClass $asStdClass = null,
    ) {
    }
}
