<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Параметр $extra не сопоставляется ни с одним свойством, не имеет
 * BindRequestParam, значения по умолчанию и не nullable — конструктор
 * вызвать невозможно, ожидается ValidRequestException.
 */
#[ValidRequest]
class ExtraRequiredParamRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(string $title, string $extra)
    {
        $this->title = $title;
    }
}
