<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\EnumCast\EnumCastRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class EnumCastController extends AbstractTestController
{
    /**
     * Приведение string-backed (not null) и int-backed (nullable) enum.
     */
    #[Route('/caster/enum', methods: ['POST'])]
    public function enum(
        EnumCastRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'status' => $request->status->value,
                'priority' => $request->priority?->value,
            ],
        );
    }
}
