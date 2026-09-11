<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\BindToMissingPropertyRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\BindToOtherPropertyRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\DoublePatcherRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\ExtraDefaultParamRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\ExtraNullableParamRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\ExtraRequiredParamRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\MatchByBindNameRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\MatchByNameRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\PatcherOnExtraParamRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Constructor\SourceOnConstructorParamRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ConstructorController extends AbstractTestController
{
    #[Route('/constructor/match-by-name', methods: ['POST'])]
    public function matchByName(
        #[ValidRequest]
        MatchByNameRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['title' => $request->title]);
    }

    #[Route('/constructor/match-by-bind-name', methods: ['POST'])]
    public function matchByBindName(
        #[ValidRequest]
        MatchByBindNameRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['title' => $request->title]);
    }

    /**
     * Без группы Mirror: каждый параметр берёт своё одноимённое поле.
     */
    #[Route('/constructor/bind-to-other/plain', methods: ['POST'])]
    public function bindToOtherPlain(
        #[ValidRequest]
        BindToOtherPropertyRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['first' => $request->first, 'second' => $request->second]);
    }

    /**
     * Группа Mirror: параметр second привязан к полю first — оба одинаковые.
     */
    #[Route('/constructor/bind-to-other/mirror', methods: ['POST'])]
    public function bindToOtherMirror(
        #[ValidRequest(groups: ['Default', 'Mirror'])]
        BindToOtherPropertyRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['first' => $request->first, 'second' => $request->second]);
    }

    #[Route('/constructor/extra-default', methods: ['POST'])]
    public function extraDefault(
        #[ValidRequest]
        ExtraDefaultParamRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['title' => $request->title]);
    }

    #[Route('/constructor/extra-nullable', methods: ['POST'])]
    public function extraNullable(
        #[ValidRequest]
        ExtraNullableParamRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['title' => $request->title]);
    }

    #[Route('/constructor/extra-required', methods: ['POST'])]
    public function extraRequired(
        #[ValidRequest]
        ExtraRequiredParamRequest $request,
    ): JsonResponse {
        return new JsonResponse(['title' => $request->title]);
    }

    #[Route('/constructor/bind-to-missing', methods: ['POST'])]
    public function bindToMissing(
        #[ValidRequest]
        BindToMissingPropertyRequest $request,
    ): JsonResponse {
        return new JsonResponse(['title' => $request->title]);
    }

    #[Route('/constructor/source-on-param', methods: ['POST'])]
    public function sourceOnParam(
        #[ValidRequest]
        SourceOnConstructorParamRequest $request,
    ): JsonResponse {
        return new JsonResponse(['title' => $request->title]);
    }
}
