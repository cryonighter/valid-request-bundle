<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тип свойства — обычный класс с наследниками, нужен именно наследник.
 */
#[ValidRequest]
class ResolveBySubclassRequest
{
    public function __construct(
        #[Assert\Type(type: EmailNotification::class)]
        #[Assert\Valid]
        public Notification $notification,
    ) {}
}
