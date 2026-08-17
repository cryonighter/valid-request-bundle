<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ExtraFieldsQueryRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ExtraFieldsRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ExtraFieldsController extends AbstractTestController
{
    /**
     * payload-источник: бандл автоматически применяет
     * NoExtraFields к полям, которых нет в классе DTO.
     */
    #[Route('/extra-fields/payload', methods: ['POST'])]
    public function payload(
        ExtraFieldsRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'name' => $request->name,
                'email' => $request->email,
            ],
        );
    }

    /**
     * query-источник: лишние параметры не проверяются,
     * NoExtraFields для этого источника не применяется.
     */
    #[Route('/extra-fields/query', methods: ['GET'])]
    public function query(
        ExtraFieldsQueryRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['page' => $request->page]);
    }
}
