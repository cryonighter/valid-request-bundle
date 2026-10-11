<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Unbind;

use Cryonighter\ValidRequestBundle\Attribute\UnbindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Свойство $reference отвязано от запроса только в группе Internal.
 *
 * В группе Internal значение берётся из значения по умолчанию.
 * В остальных группах свойство связывается как обычное поле запроса.
 */
#[ValidRequest]
class GroupedUnbindRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[UnbindRequestParam(groups: ['Internal'])]
        #[Assert\NotBlank]
        public string $reference = 'computed-ref',
    ) {}
}
