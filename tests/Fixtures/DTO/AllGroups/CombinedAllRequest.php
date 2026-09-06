<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Два Assert\All (каждый в своей группе — AllWizards / AllWarriors),
 * внутри каждого свой Assert\Type (тоже в своей группе — TypeWizard / TypeWarrior).
 *
 * Итого 4 группы. Ветка сработает только если в запросе присутствуют
 * ОБЕ группы соответствующей пары (например ['AllWizards', 'TypeWizard']).
 * Несогласованная пара оставит массив непровалидированным.
 */
readonly class CombinedAllRequest
{
    public function __construct(
        #[Assert\All(
            constraints: [new Assert\Type(type: Wizard::class, groups: ['TypeWizard'])],
            groups: ['AllWizards', 'TypeWizard'],
        )]
        #[Assert\All(
            constraints: [new Assert\Type(type: Warrior::class, groups: ['TypeWarrior'])],
            groups: ['AllWarriors', 'TypeWarrior'],
        )]
        #[Assert\Valid]
        public array $party,
    ) {}
}
