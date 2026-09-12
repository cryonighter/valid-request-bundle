<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use stdClass;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * iterable-тип свойства + объектный Assert\Type.
 * Тип свойства — коллекция, а Assert\Type указывает на объект.
 * Проверяем итоговое поведение (сборка одного объекта или исключение/ошибка).
 */
#[ValidRequest]
class IterableObjectConflictRequest
{
    public function __construct(
        #[Assert\Type(stdClass::class)]
        public array $value,
    ) {
    }
}
