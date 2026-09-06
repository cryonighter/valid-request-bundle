<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Один Assert\All с двумя Assert\Type внутри.
 * Каждый Assert\Type целится в свой класс и имеет свою группу.
 * Активной становится только та ветка, чья группа совпадает с группой запроса.
 */
readonly class AllTypeGroupsRequest
{
    public function __construct(
        #[Assert\All([
            new Assert\Type(type: Wizard::class, groups: ['Wizards']),
            new Assert\Type(type: Warrior::class, groups: ['Warriors']),
        ])]
        #[Assert\Valid]
        public array $party,
    ) {}
}
