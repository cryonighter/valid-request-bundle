<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DateTimeCast\DateTimeCastRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DateTimeCastController extends AbstractTestController
{
    /**
     * Приведение DateTimeImmutable (not null) и DateTime (nullable).
     */
    #[Route('/caster/datetime', methods: ['POST'])]
    public function datetime(
        DateTimeCastRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'createdAt' => $request->createdAt->format('Y-m-d H:i:s.u'),
                'updatedAt' => $request->updatedAt?->format('Y-m-d H:i:s.u'),
            ],
        );
    }
}
