<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Два отдельных Assert\All, каждый в своей группе.
 * Внутри каждого — Assert\Type без собственной группы (наследует группу от Assert\All).
 */
readonly class GroupedAllRequest
{
    public function __construct(
        #[Assert\All(
            constraints: [new Assert\Type(type: Wizard::class)],
            groups: ['Wizards'],
        )]
        #[Assert\All(
            constraints: [new Assert\Type(type: Warrior::class)],
            groups: ['Warriors'],
        )]
        #[Assert\Valid]
        public array $party,
    ) {}
}
