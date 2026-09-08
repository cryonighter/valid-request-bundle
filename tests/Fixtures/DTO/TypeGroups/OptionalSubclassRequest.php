<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeGroups;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тайпхинт — конкретный класс Notification.
 * Единственный Assert\Type целится в наследника EmailNotification и имеет группу Email.
 *
 * Если в запросе активна группа Email — берётся класс из Assert\Type (EmailNotification).
 * Если группа Email не активна — Assert\Type не срабатывает, и класс берётся из тайпхинта (Notification).
 */
#[ValidRequest]
class OptionalSubclassRequest
{
    public function __construct(
        #[Assert\Type(type: EmailNotification::class, groups: ['Email'])]
        #[Assert\Valid]
        public Notification $notification,
    ) {}
}
