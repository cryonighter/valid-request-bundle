<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Symfony\Component\Validator\Constraints as Assert;

class Notification
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public string $subject,
    ) {}
}
