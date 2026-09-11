<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Параметр $extra не сопоставляется ни с одним свойством и не имеет
 * BindRequestParam, но имеет значение по умолчанию — объект собирается,
 * параметр ни на что не влияет.
 */
#[ValidRequest]
class ExtraDefaultParamRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(string $title, string $extra = 'default')
    {
        $this->title = $title;
    }
}
