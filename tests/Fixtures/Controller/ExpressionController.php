<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Expression\ExpressionRequest;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Группы валидации, вычисляемые выражениями.
 * Источник данных — тело запроса, выбор группы — по query-параметрам и заголовкам.
 */
class ExpressionController extends AbstractTestController
{
    /**
     * Группа задана объектом Expression: query-параметр mode=extra выбирает группу Extra, иначе Default.
     */
    #[Route('/expression/object', methods: ['POST'])]
    public function expressionObject(
        #[ValidRequest(groups: [new Expression("request.query.get('mode') == 'extra' ? 'Extra' : 'Default'")])]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Краткая форма: строка с префиксом "expr:" вычисляется как выражение.
     */
    #[Route('/expression/prefixed', methods: ['POST'])]
    public function prefixed(
        #[ValidRequest(groups: ["expr:request.query.get('mode') == 'extra' ? 'Extra' : 'Default'"])]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Краткая форма, когда groups — одна строка, а не массив.
     */
    #[Route('/expression/prefixed-string', methods: ['POST'])]
    public function prefixedString(
        #[ValidRequest(groups: "expr:request.query.get('mode') == 'extra' ? 'Extra' : 'Default'")]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Обычная группа и выражение в одном списке: Default всегда, Extra — только при наличии query-параметра extra.
     */
    #[Route('/expression/mixed', methods: ['POST'])]
    public function mixed(
        #[ValidRequest(groups: ['Default', "expr:request.query.has('extra') ? 'Extra' : 'Default'"])]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Выражение возвращает массив групп, он разворачивается в общий список.
     */
    #[Route('/expression/array-result', methods: ['POST'])]
    public function arrayResult(
        #[ValidRequest(groups: ["expr:request.query.has('extra') ? ['Default', 'Extra'] : ['Default']"])]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Выражение внутри GroupSequence.
     */
    #[Route('/expression/in-sequence', methods: ['POST'])]
    public function inSequence(
        #[ValidRequest(groups: new GroupSequence(['Default', "expr:request.query.get('mode') == 'extra' ? 'Extra' : 'Default'"]))]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Выражение может обращаться к любым данным запроса, например к заголовкам.
     */
    #[Route('/expression/by-header', methods: ['POST'])]
    public function byHeader(
        #[ValidRequest(groups: ["expr:request.headers.get('X-Scope') == 'extra' ? 'Extra' : 'Default'"])]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    /**
     * Выражение возвращает не строку — это ошибка конфигурации.
     */
    #[Route('/expression/invalid-result', methods: ['POST'])]
    public function invalidResult(
        #[ValidRequest(groups: ["expr:42"])]
        ExpressionRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        return $this->respond($request, $violations);
    }

    private function respond(ExpressionRequest $request, ?ConstraintViolationListInterface $violations): JsonResponse
    {
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
}
