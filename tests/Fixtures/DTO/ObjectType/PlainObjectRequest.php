<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use stdClass;

/**
 * Свойства без Assert\Type: целевой класс определяется только типом свойства.
 *
 * - object  → приводится к stdClass
 * - stdClass → инстанцируется как есть
 */
#[ValidRequest]
class PlainObjectRequest
{
    public function __construct(
        public object $asObject,

        public stdClass $asStdClass,
    ) {
    }
}
