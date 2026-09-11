<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Параметр $extra не сопоставляется ни с одним свойством, не имеет
 * BindRequestParam и значения по умолчанию, но имеет nullable-тип —
 * объект собирается, параметр получает null и ни на что не влияет.
 */
#[ValidRequest]
class ExtraNullableParamRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(string $title, ?string $extra)
    {
        $this->title = $title;
    }
}
