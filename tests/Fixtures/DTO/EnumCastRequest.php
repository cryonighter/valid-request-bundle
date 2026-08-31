<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Enum\OrderStatus;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Enum\Priority;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
class EnumCastRequest
{
    public function __construct(
        #[Assert\NotNull]
        public OrderStatus $status,

        // nullable int-backed enum
        public ?Priority $priority,
    ) {
    }
}
