<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeCastBoolRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeCastFloatRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeCastIntRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\TypeCastRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class TypeCastController extends AbstractTestController
{
    /**
     * Все три типа сразу: bool, int, float.
     */
    #[Route('/typecast/all', methods: ['POST'])]
    public function all(
        ?TypeCastRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'active' => $request?->active,
                'count' => $request?->count,
                'price' => $request?->price,
            ],
        );
    }

    /**
     * Изолированное тестирование bool.
     */
    #[Route('/typecast/bool', methods: ['POST'])]
    public function bool(
        ?TypeCastBoolRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['flag' => $request?->flag]);
    }

    /**
     * Изолированное тестирование int.
     */
    #[Route('/typecast/int', methods: ['POST'])]
    public function int(
        ?TypeCastIntRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['amount' => $request?->amount]);
    }

    /**
     * Изолированное тестирование float.
     */
    #[Route('/typecast/float', methods: ['POST'])]
    public function float(
        ?TypeCastFloatRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['price' => $request?->price]);
    }
}
