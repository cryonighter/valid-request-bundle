<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use DateTime;
use DateTimeImmutable;

#[ValidRequest]
class DateTimeCastRequest
{
    public function __construct(
        public DateTimeImmutable $createdAt,
        public ?DateTime $updatedAt,
    ) {
    }
}
