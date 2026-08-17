<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\AttributeRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\CookieRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\HeaderRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\MixedSourceRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\NamedParamRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\QueryFilterRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\ServerRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SourceController extends AbstractTestController
{
    /**
     * Источник query: данные из строки запроса (?page=1&limit=10).
     */
    #[Route('/source/query', methods: ['GET'])]
    public function query(
        QueryFilterRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'page' => $request->page,
                'limit' => $request->limit,
            ],
        );
    }

    /**
     * Источник attributes: данные из параметров роута ({id}).
     */
    #[Route('/source/attributes/{id}', methods: ['GET'])]
    public function attributes(
        AttributeRequest $request,
    ): JsonResponse {
        return new JsonResponse(['id' => $request->id]);
    }

    /**
     * Источник cookies: данные из cookie-заголовка.
     */
    #[Route('/source/cookies', methods: ['GET'])]
    public function cookies(
        CookieRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['session' => $request->session]);
    }

    /**
     * Источник headers: данные из заголовков HTTP-запроса.
     */
    #[Route('/source/headers', methods: ['GET'])]
    public function headers(
        HeaderRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['token' => $request->token]);
    }

    /**
     * Источник server: данные из серверных переменных.
     */
    #[Route('/source/server', methods: ['GET'])]
    public function server(
        ServerRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['server_user' => $request->serverUser]);
    }

    /**
     * Смешивание источников: id из атрибутов роута, name из тела запроса.
     */
    #[Route('/source/mixed/{id}', methods: ['POST'])]
    public function mixed(
        MixedSourceRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'id' => $request->id,
                'name' => $request->name,
            ],
        );
    }

    /**
     * BindRequestParam(name: ...): поле в запросе называется user_id,
     * а свойство класса — userId.
     */
    #[Route('/source/named-param', methods: ['POST'])]
    public function namedParam(
        NamedParamRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['user_id' => $request->userId]);
    }
}
