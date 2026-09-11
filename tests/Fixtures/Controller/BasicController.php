<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Basic\AuthRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class BasicController extends AbstractTestController
{
    /**
     * Not nullable — атрибут #[ValidRequest] указан на классе DTO.
     * При невалидных данных бандл должен бросить ValidationFailedException.
     */
    #[Route('/basic/not-nullable', methods: ['POST'])]
    public function notNullable(AuthRequest $request): JsonResponse
    {
        return new JsonResponse(
            [
                'login' => $request->login,
                'password' => $request->password,
            ],
        );
    }

    /**
     * Nullable — атрибут #[ValidRequest] указан на аргументе контроллера.
     * При отсутствии данных в запросе передаётся null.
     */
    #[Route('/basic/nullable', methods: ['GET', 'POST'])]
    public function nullable(#[ValidRequest] ?AuthRequest $request): JsonResponse
    {
        if ($request === null) {
            return new JsonResponse(['result' => 'empty']);
        }

        return new JsonResponse(
            [
                'login' => $request->login,
                'password' => $request->password,
            ],
        );
    }

    /**
     * Violations nullable — при успешной валидации $violations будет null,
     * при ошибках — содержит список нарушений. Исключение не бросается.
     */
    #[Route('/basic/violations-nullable', methods: ['POST'])]
    public function violationsNullable(
        ?AuthRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'login' => $request?->login,
                'password' => $request?->password,
            ],
        );
    }

    /**
     * Violations not nullable — при успешной валидации передаётся пустой список,
     * а не null. Исключение не бросается.
     */
    #[Route('/basic/violations-not-nullable', methods: ['POST'])]
    public function violationsNotNullable(
        ?AuthRequest $request,
        ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return new JsonResponse(
            [
                'errors' => $this->formatViolations($violations),
                'count' => count($violations),
            ],
        );
    }

    /**
     * Два ValidRequest-аргумента подряд — violations относятся
     * строго к предшествующему аргументу, порядок важен.
     */
    #[Route('/basic/multiple', methods: ['POST'])]
    public function multiple(
        ?AuthRequest $first,
        ?ConstraintViolationListInterface $firstViolations,
        ?AuthRequest $second,
        ?ConstraintViolationListInterface $secondViolations,
    ): JsonResponse {
        return new JsonResponse(
            [
                'first_errors' => $firstViolations !== null ? count($firstViolations) : 0,
                'second_errors' => $secondViolations !== null ? count($secondViolations) : 0,
            ],
        );
    }
}
