<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType\ConflictTypeRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType\NestedObjectRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType\NullableObjectRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType\PlainObjectRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ObjectType\TypedObjectRequest;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ObjectTypeController extends AbstractTestController
{
    #[Route('/object-type/plain', methods: ['POST'])]
    public function plain(
        #[ValidRequest]
        PlainObjectRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse([
            'asObject' => $this->describe($request->asObject),
            'asStdClass' => $this->describe($request->asStdClass),
        ]);
    }

    #[Route('/object-type/typed', methods: ['POST'])]
    public function typed(
        #[ValidRequest]
        TypedObjectRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse([
            'stdClassTypedStd' => $this->describe($request->stdClassTypedStd),
            'stdClassTypedObject' => $this->describe($request->stdClassTypedObject),
            'objectTypedObject' => $this->describe($request->objectTypedObject),
            'mixedTypedObject' => $this->describe($request->mixedTypedObject),
            'mixedTypedStd' => $this->describe($request->mixedTypedStd),
        ]);
    }

    /**
     * Объектный тип свойства + примитивный Assert\Type.
     * При корректных для примитива данных — сборка объекта невозможна.
     */
    #[Route('/object-type/conflict', methods: ['POST'])]
    public function conflict(
        #[ValidRequest]
        ConflictTypeRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['value' => $this->describe($request->value)]);
    }

    /**
     * Возвращает рекурсивное описание вложенной структуры object-свойства:
     * на каждом уровне — тип значения (stdClass / array / скаляр).
     */
    #[Route('/object-type/nested', methods: ['POST'])]
    public function nested(
        #[ValidRequest]
        NestedObjectRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['data' => $this->describeDeep($request->data)]);
    }

    /**
     * Nullable object-свойства: отдаём тип каждого свойства (null или stdClass).
     */
    #[Route('/object-type/nullable', methods: ['POST'])]
    public function nullable(
        #[ValidRequest]
        NullableObjectRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse([
            'asObject' => get_debug_type($request->asObject),
            'asStdClass' => get_debug_type($request->asStdClass),
        ]);
    }

    /**
     * Рекурсивно описывает значение: для stdClass и array возвращает
     * тип и рекурсивно описанное содержимое, для скаляров — сам тип.
     */
    private function describeDeep(mixed $value): array
    {
        if ($value instanceof stdClass) {
            return [
                'type' => 'stdClass',
                'fields' => array_map($this->describeDeep(...), (array) $value),
            ];
        }

        if (is_array($value)) {
            return [
                'type' => 'array',
                'list' => array_is_list($value),
                'items' => array_map($this->describeDeep(...), $value),
            ];
        }

        return ['type' => get_debug_type($value), 'value' => $value];
    }

    /**
     * Возвращает класс значения и его поля, чтобы тест мог проверить и то,
     * что это stdClass, и то, что поля совпали с полями запроса.
     *
     * @return array{class: string, fields: array<string, mixed>}
     */
    private function describe(mixed $value): array
    {
        return [
            'class' => get_debug_type($value),
            'fields' => (array) $value,
        ];
    }
}
