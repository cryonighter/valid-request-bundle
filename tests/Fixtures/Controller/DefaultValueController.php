<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue\DefaultValueRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue\NullableDefaultValueRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue\ObjectPatcherDefaultValueRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue\PropertyPatcherDefaultValueRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
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

    /**
     * Nullable-аргумент: при отсутствии входных данных ожидается null,
     * даже если у части свойств есть значения по умолчанию.
     */
    #[Route('/default-value/nullable', methods: ['GET', 'POST'])]
    public function nullable(
        ?NullableDefaultValueRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->describeNullableResult($request, $violations);
    }

    /**
     * Not nullable аргумент: при отсутствии входных данных null недопустим,
     * данные из значений по умолчанию должны уйти в валидацию.
     */
    #[Route('/default-value/required', methods: ['GET', 'POST'])]
    public function required(
        NullableDefaultValueRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->describeNullableResult($request, $violations);
    }

    /**
     * Nullable-аргумент с патчером свойства.
     */
    #[Route('/default-value/property-patcher', methods: ['GET', 'POST'])]
    public function propertyPatcher(
        ?PropertyPatcherDefaultValueRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->describeNullableResult($request, $violations);
    }

    /**
     * Nullable-аргумент с патчером объекта.
     */
    #[Route('/default-value/object-patcher', methods: ['GET', 'POST'])]
    public function objectPatcher(
        ?ObjectPatcherDefaultValueRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->describeNullableResult($request, $violations);
    }

    /**
     * Формирует ответ, по которому однозначно видно, что пришло в контроллер:
     * null, собранный объект или список ошибок валидации.
     */
    private function describeNullableResult(
        ?object $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null && count($violations) > 0) {
            return new JsonResponse(
                [
                    'dto' => null,
                    'violations' => array_map(
                        static fn(ConstraintViolationInterface $violation): string => $violation->getPropertyPath(),
                        iterator_to_array($violations),
                    ),
                ],
            );
        }

        return new JsonResponse(
            [
                'dto' => $request === null ? null : get_object_vars($request),
                'violations' => null,
            ],
        );
    }
}
