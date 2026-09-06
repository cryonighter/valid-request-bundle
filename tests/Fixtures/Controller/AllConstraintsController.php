<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups\AllTypeGroupsRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups\CombinedAllRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups\GroupedAllRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups\NestedAllRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups\Warrior;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AllGroups\Wizard;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class AllConstraintsController extends AbstractTestController
{
    // =========================================================================
    // Кейс 1: один Assert\All, внутри два Assert\Type — каждый со своей группой
    // =========================================================================

    #[Route('/all/type-groups/wizards', methods: ['POST'])]
    public function allTypeGroupsWizards(
        #[ValidRequest(groups: ['Default', 'Wizards'])]
        AllTypeGroupsRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeParty($request->party);
    }

    #[Route('/all/type-groups/warriors', methods: ['POST'])]
    public function allTypeGroupsWarriors(
        #[ValidRequest(groups: ['Default', 'Warriors'])]
        AllTypeGroupsRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeParty($request->party);
    }

    // =========================================================================
    // Кейс 2: два Assert\All (каждый в своей группе), внутри Assert\Type без группы
    // =========================================================================

    #[Route('/all/grouped/wizards', methods: ['POST'])]
    public function groupedAllWizards(
        #[ValidRequest(groups: ['Default', 'Wizards'])]
        GroupedAllRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeParty($request->party);
    }

    #[Route('/all/grouped/warriors', methods: ['POST'])]
    public function groupedAllWarriors(
        #[ValidRequest(groups: ['Default', 'Warriors'])]
        GroupedAllRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeParty($request->party);
    }

    // =========================================================================
    // Кейс 3: комбинированный — 2 Assert\All + 2 Assert\Type, всего 4 группы
    // =========================================================================

    /**
     * Согласованная пара для магов: All-группа + Type-группа одной ветки.
     */
    #[Route('/all/combined/wizards-matched', methods: ['POST'])]
    public function combinedWizardsMatched(
        #[ValidRequest(groups: ['Default', 'AllWizards', 'TypeWizard'])]
        CombinedAllRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeParty($request->party);
    }

    /**
     * Согласованная пара для воинов: All-группа + Type-группа одной ветки.
     */
    #[Route('/all/combined/warriors-matched', methods: ['POST'])]
    public function combinedWarriorsMatched(
        #[ValidRequest(groups: ['Default', 'AllWarriors', 'TypeWarrior'])]
        CombinedAllRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeParty($request->party);
    }

    // =========================================================================
    // Кейс 4: вырожденный — Assert\All внутри Assert\All, два Assert\Type с группами
    // =========================================================================

    #[Route('/all/nested/wizards', methods: ['POST'])]
    public function nestedAllWizards(
        #[ValidRequest(groups: ['Default', 'Wizards'])]
        NestedAllRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeSquads($request->squads);
    }

    #[Route('/all/nested/warriors', methods: ['POST'])]
    public function nestedAllWarriors(
        #[ValidRequest(groups: ['Default', 'Warriors'])]
        NestedAllRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return $this->describeSquads($request->squads);
    }

    /**
     * @param array<int, mixed> $party
     */
    private function describeParty(array $party): JsonResponse
    {
        return new JsonResponse(['party' => array_map($this->describeMember(...), $party)]);
    }

    /**
     * @param array<int, array<int, mixed>> $squads
     */
    private function describeSquads(array $squads): JsonResponse
    {
        return new JsonResponse([
            'squads' => array_map(
                fn (array $squad): array => array_map($this->describeMember(...), $squad),
                $squads,
            ),
        ]);
    }

    private function describeMember(mixed $member): array
    {
        if ($member instanceof Wizard) {
            return ['class' => 'Wizard', 'name' => $member->name, 'spellPower' => $member->spellPower];
        }

        if ($member instanceof Warrior) {
            return ['class' => 'Warrior', 'name' => $member->name, 'strength' => $member->strength];
        }

        return ['class' => get_debug_type($member)];
    }
}
