<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Unbind\ComputedFieldRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Unbind\GroupedUnbindRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class UnbindController extends AbstractTestController
{
    /**
     * Безусловный: slug исключён из связывания и вычисляется в конструкторе.
     */
    #[Route('/unbind/computed', methods: ['POST'])]
    public function computed(
        ComputedFieldRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'title' => $request->title,
                'slug' => $request->slug,
            ],
        );
    }

    /**
     * Группа Internal: reference отвязан, значение берётся из значения по умолчанию.
     */
    #[Route('/unbind/grouped/internal', methods: ['POST'])]
    public function groupedInternal(
        #[ValidRequest(groups: ['Default', 'Internal'])]
        GroupedUnbindRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'name' => $request->name,
                'reference' => $request->reference,
            ],
        );
    }

    /**
     * Группа Public: reference связывается как обычное поле запроса.
     */
    #[Route('/unbind/grouped/public', methods: ['POST'])]
    public function groupedPublic(
        #[ValidRequest(groups: ['Default', 'Public'])]
        GroupedUnbindRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'name' => $request->name,
                'reference' => $request->reference,
            ],
        );
    }
}
