<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;

/**
 * Свойство публичное, но readonly и не инициализируется конструктором.
 * Прямое присваивание невозможно, поэтому гидрация уходит в рефлексию.
 */
#[ValidRequest]
class PublicReadonlyRequest
{
    #[BindRequestParam(name: 'login')]
    public readonly string $username;
}
