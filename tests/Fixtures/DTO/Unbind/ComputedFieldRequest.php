<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Unbind;

use Cryonighter\ValidRequestBundle\Attribute\UnbindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойство $slug отвязано от запроса и вычисляется в конструкторе из $title.
 */
#[ValidRequest]
class ComputedFieldRequest
{
    #[UnbindRequestParam]
    #[Assert\NotBlank]
    public string $slug;

    public function __construct(
        #[Assert\NotBlank]
        public string $title,
    ) {
        $this->slug = strtolower(str_replace(' ', '-', $this->title));
    }
}
