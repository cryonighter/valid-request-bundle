<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType\IterableObjectConflictRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType\IterablePrimitiveConflictRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType\NullableIterableRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType\PlainIterableRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\IterableType\TypedIterableRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class IterableTypeController extends AbstractTestController
{
    #[Route('/iterable-type/plain', methods: ['POST'])]
    public function plain(
        #[ValidRequest]
        PlainIterableRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['value' => $this->describe($request->value)]);
    }

    #[Route('/iterable-type/typed', methods: ['POST'])]
    public function typed(
        #[ValidRequest]
        TypedIterableRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse([
            'iterableTypedArray' => $this->describe($request->iterableTypedArray),
            'iterableTypedIterable' => $this->describe($request->iterableTypedIterable),
            'arrayTypedArray' => $this->describe($request->arrayTypedArray),
            'mixedTypedArray' => $this->describe($request->mixedTypedArray),
            'mixedTypedIterable' => $this->describe($request->mixedTypedIterable),
        ]);
    }

    /**
     * Nullable iterable-свойство: отдаём его тип (null или array).
     */
    #[Route('/iterable-type/nullable', methods: ['POST'])]
    public function nullable(
        #[ValidRequest]
        NullableIterableRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['type' => get_debug_type($request->value)]);
    }

    /**
     * iterable-тип свойства + примитивный Assert\Type.
     */
    #[Route('/iterable-type/conflict-primitive', methods: ['POST'])]
    public function conflictPrimitive(
        #[ValidRequest]
        IterablePrimitiveConflictRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['value' => $this->describe($request->value)]);
    }

    /**
     * iterable-тип свойства + объектный Assert\Type.
     */
    #[Route('/iterable-type/conflict-object', methods: ['POST'])]
    public function conflictObject(
        #[ValidRequest]
        IterableObjectConflictRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['value' => $this->describe($request->value)]);
    }

    /**
     * Возвращает тип значения, признак индексного массива и само содержимое.
     *
     * @return array{type: string, list: bool, items: mixed}
     */
    private function describe(mixed $value): array
    {
        return [
            'type' => get_debug_type($value),
            'list' => is_array($value) && array_is_list($value),
            'items' => $value,
        ];
    }
}
