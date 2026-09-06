<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Группированный массив (массив массивов).
 * Внешний Assert\All применяется к каждой группе элементов,
 * внутренний Assert\All — к каждому элементу внутри группы,
 * а два Assert\Type целятся в свои классы и имеют свои группы.
 */
readonly class NestedAllRequest
{
    public function __construct(
        #[Assert\All([
            new Assert\All([
                new Assert\Type(type: Wizard::class, groups: ['Wizards']),
                new Assert\Type(type: Warrior::class, groups: ['Warriors']),
            ]),
        ])]
        #[Assert\Valid]
        public array $squads,
    ) {}
}
