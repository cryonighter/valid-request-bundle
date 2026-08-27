<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Controller;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\CheckoutOrderRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\OrderLineRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CheckoutController extends AbstractTestController
{
    /**
     * Оформление заказа: вложенный адрес, массив позиций, дефолты и бинды.
     */
    #[Route('/checkout/order', methods: ['POST'])]
    public function order(
        CheckoutOrderRequest $request,
        ?ConstraintViolationListInterface $violations,
    ): JsonResponse {
        if ($violations !== null) {
            return $this->createErrorResponse($violations);
        }

        return new JsonResponse(
            [
                'orderId' => $request->orderId,
                'email' => $request->email,
                'deliveryAddress' => [
                    'country' => $request->deliveryAddress->country,
                    'city' => $request->deliveryAddress->city,
                    'zip' => $request->deliveryAddress->postalCode,
                    'street' => $request->deliveryAddress->street,
                    'preferredLanguage' => $request->deliveryAddress->preferredLanguage,
                ],
                'lines' => array_map(
                    fn (OrderLineRequest $line): array => [
                        'articleCode' => $line->articleCode,
                        'quantity' => $line->quantity,
                        'unitPrice' => $line->unitPrice,
                        'note' => $line->note,
                    ],
                    $request->lines,
                ),
                'giftWrap' => $request->giftWrap === null ? null : [
                    'style' => $request->giftWrap->style,
                    'message' => $request->giftWrap->message,
                ],
                'shippingMethod' => $request->shippingMethod,
                'bonusPoints' => $request->bonusPoints,
                'comment' => $request->comment,
            ],
        );
    }
}
