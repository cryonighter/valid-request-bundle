<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class AbstractTestController
{
    protected function createErrorResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        return new JsonResponse(
            [
                'errors' => $this->formatViolations($violations),
            ],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    protected function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }
}
