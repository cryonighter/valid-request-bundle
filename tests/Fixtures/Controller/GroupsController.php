<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Groups\CountryRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Groups\GroupedRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Groups\MultiBindRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class GroupsController extends AbstractTestController
{
    /**
     * Группы Default + Create: code берётся из тела запроса (payload),
     * т.к. BindRequestParam(source: attributes) применяется только для группы Update.
     */
    #[Route('/groups/country', methods: ['POST'])]
    public function createCountry(
        #[ValidRequest(groups: ['Default', 'Create'])]
        CountryRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'code' => $request->code,
                'name' => $request->name,
            ],
        );
    }

    /**
     * Группы Default + Update: code берётся из атрибутов роута ({code}),
     * т.к. BindRequestParam(source: attributes, groups: ['Update']) совпадает.
     */
    #[Route('/groups/country/{code}', methods: ['PUT'])]
    public function updateCountry(
        #[ValidRequest(groups: ['Default', 'Update'])]
        CountryRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'code' => $request->code,
                'name' => $request->name,
            ],
        );
    }

    /**
     * Только группа Default: валидируются поля email и name.
     * Поле phone с группой Extra не валидируется — violation для него не возникает.
     */
    #[Route('/groups/grouped/default', methods: ['POST'])]
    public function groupedDefault(
        #[ValidRequest(groups: ['Default'])]
        GroupedRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
            ],
        );
    }

    /**
     * Только группа Extra: валидируются поля phone и name.
     * Поле email с группой Default не валидируется — violation для него не возникает.
     */
    #[Route('/groups/grouped/extra', methods: ['POST'])]
    public function groupedExtra(
        #[ValidRequest(groups: ['Extra'])]
        GroupedRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
            ],
        );
    }

    /**
     * Группы Default + Extra: валидируются все поля из обеих групп.
     */
    #[Route('/groups/grouped/both', methods: ['POST'])]
    public function groupedBoth(
        #[ValidRequest(groups: ['Default', 'Extra'])]
        GroupedRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
            ],
        );
    }

    /**
     * Группа Web: токен берётся из query-параметра access_token (активен первый BindRequestParam).
     */
    #[Route('/multi-bind/web', methods: ['GET'])]
    public function web(
        #[ValidRequest(groups: ['Default', 'Web'])]
        MultiBindRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['token' => $request->token]);
    }

    /**
     * Группа Api: токен берётся из заголовка X-Api-Token (активен второй BindRequestParam).
     */
    #[Route('/multi-bind/api', methods: ['GET'])]
    public function api(
        #[ValidRequest(groups: ['Default', 'Api'])]
        MultiBindRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(['token' => $request->token]);
    }
}
