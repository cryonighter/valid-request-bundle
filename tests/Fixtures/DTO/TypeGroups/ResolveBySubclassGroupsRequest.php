<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Два Assert\Type с группами: выбираем конкретного наследника по активной группе.
 */
#[ValidRequest]
class ResolveBySubclassGroupsRequest
{
    public function __construct(
        #[Assert\Type(type: EmailNotification::class, groups: ['Email'])]
        #[Assert\Type(type: SmsNotification::class, groups: ['Sms'])]
        #[Assert\Valid]
        public Notification $notification,
    ) {}
}
