<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Symfony\Component\Validator\Constraints as Assert;

class EmailNotification extends Notification
{
    public function __construct(
        string $subject,

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $to,
    ) {
        parent::__construct($subject);
    }
}
