<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Объектный тип свойства + примитивный Assert\Type — конфликт.
 *
 * Если данные соответствуют примитиву: валидация проходит, но объект собрать
 * невозможно → ValidRequestException.
 *
 * Если данные соответствуют объекту: примитивный Assert\Type не проходит →
 * ошибка валидации.
 */
#[ValidRequest]
class ConflictTypeRequest
{
    public function __construct(
        #[Assert\Type('int')]
        public object $value,
    ) {
    }
}
