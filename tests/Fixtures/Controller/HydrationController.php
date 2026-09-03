<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\CombinedRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\ConstructorRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\PrivateSetterRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\PublicPropertyRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\PublicReadonlyRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\ReflectionRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\SetterRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Hydration\TwoArgsSetterRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class HydrationController extends AbstractTestController
{
    /**
     * Сборка через конструктор с переименованием параметра.
     */
    #[Route('/builder/constructor', methods: ['POST'])]
    public function constructor(
        ConstructorRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->getUsername()]);
    }

    /**
     * Сборка через публичный сеттер.
     */
    #[Route('/builder/setter', methods: ['POST'])]
    public function setter(
        SetterRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->getUsername()]);
    }

    /**
     * Сборка через публичное свойство.
     */
    #[Route('/builder/public-property', methods: ['POST'])]
    public function publicProperty(
        PublicPropertyRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->username]);
    }

    /**
     * Сборка через рефлексию.
     */
    #[Route('/builder/reflection', methods: ['POST'])]
    public function reflection(
        ReflectionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->getUsername()]);
    }

    /**
     * Комбинированная сборка: каждое из четырёх свойств гидрируется своей стратегией.
     */
    #[Route('/builder/combined', methods: ['POST'])]
    public function combined(
        CombinedRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'viaConstructor' => $request->getViaConstructor(),
                'viaConstructorDefault' => $request->getViaConstructorDefault(),
                'viaSetter' => $request->getViaSetter(),
                'viaPublic' => $request->viaPublic,
                'viaReflection' => $request->getViaReflection(),
                'viaDefault' => $request->getViaDefault(),
            ],
        );
    }

    /**
     * Сеттер приватный: стратегия сеттера отвергается, гидрация через рефлексию.
     */
    #[Route('/builder/private-setter', methods: ['POST'])]
    public function privateSetter(
        PrivateSetterRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->getUsername()]);
    }

    /**
     * Сеттер с двумя аргументами: стратегия сеттера отвергается, гидрация через рефлексию.
     */
    #[Route('/builder/two-args-setter', methods: ['POST'])]
    public function twoArgsSetter(
        TwoArgsSetterRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->getUsername()]);
    }

    /**
     * Публичное readonly-свойство: прямое присваивание невозможно, гидрация через рефлексию.
     */
    #[Route('/builder/public-readonly', methods: ['POST'])]
    public function publicReadonly(
        PublicReadonlyRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['username' => $request->username]);
    }
}
