<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValueRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DefaultValueController extends AbstractTestController
{
    /**
     * Проверка поведения свойств со значениями по умолчанию.
     */
    #[Route('/default-value', methods: ['POST'])]
    public function index(
        DefaultValueRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'name' => $request->name,
                'role' => $request->role,
                'comment' => $request->comment,
                'priority' => $request->priority,
            ],
        );
    }
}
