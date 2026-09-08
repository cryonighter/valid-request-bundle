<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Symfony\Component\Validator\Constraints as Assert;

class SmsNotification extends Notification
{
    public function __construct(
        string $subject,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\+\d{7,15}$/')]
        public string $phone,
    ) {
        parent::__construct($subject);
    }
}
