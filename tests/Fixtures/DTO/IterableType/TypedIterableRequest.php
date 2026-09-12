<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойства с Assert\Type: тип свойства перекрывается атрибутом.
 * Во всех комбинациях итог — обычный индексный array.
 */
#[ValidRequest]
class TypedIterableRequest
{
    public function __construct(
        // iterable + Assert\Type('array') → array
        #[Assert\Type('array')]
        public iterable $iterableTypedArray,

        // iterable + Assert\Type('iterable') → array
        #[Assert\Type('iterable')]
        public iterable $iterableTypedIterable,

        // array + Assert\Type('array') → array
        #[Assert\Type('array')]
        public array $arrayTypedArray,

        // mixed + Assert\Type('array') → array
        #[Assert\Type('array')]
        public mixed $mixedTypedArray,

        // mixed + Assert\Type('iterable') → array
        #[Assert\Type('iterable')]
        public mixed $mixedTypedIterable,
    ) {
    }
}
