<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BindRequestParam на обычном параметре конструктора задаёт source —
 * это недопустимо, ожидается ValidRequestException.
 */
#[ValidRequest]
class SourceOnConstructorParamRequest
{
    #[Assert\NotBlank]
    public string $title;

    public function __construct(
        #[BindRequestParam(source: RequestValueSource::query)]
        string $title,
    ) {
        $this->title = $title;
    }
}
