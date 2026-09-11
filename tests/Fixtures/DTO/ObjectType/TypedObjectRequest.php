<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use stdClass;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойства с Assert\Type: тип свойства перекрывается атрибутом.
 * Во всех комбинациях итог — stdClass с полями из запроса.
 */
#[ValidRequest]
class TypedObjectRequest
{
    public function __construct(
        // stdClass + Assert\Type('stdClass') → stdClass
        #[Assert\Type('stdClass')]
        public stdClass $stdClassTypedStd,

        // stdClass + Assert\Type('object') → stdClass
        #[Assert\Type('object')]
        public stdClass $stdClassTypedObject,

        // object + Assert\Type('object') → stdClass
        #[Assert\Type('object')]
        public object $objectTypedObject,

        // mixed + Assert\Type('object') → stdClass
        #[Assert\Type('object')]
        public mixed $mixedTypedObject,

        // mixed + Assert\Type('stdClass') → stdClass
        #[Assert\Type('stdClass')]
        public mixed $mixedTypedStd,
    ) {
    }
}
