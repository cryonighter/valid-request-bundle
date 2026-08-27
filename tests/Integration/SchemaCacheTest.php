<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class SchemaCacheTest extends AbstractWebTestCase
{
    /**
     * Полностью валидный запрос со всеми гранями DTO: вложенный объект,
     * массив объектов, дефолты, бинды с переименованием.
     * Объект собирается и проходит валидацию.
     */
    public function testValidCheckoutIsResolved(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/checkout/order', $this->validPayload());

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('buyer@example.com', $data['email']);
        $this->assertSame('DE', $data['deliveryAddress']['country']);
        $this->assertSame('ABC-123', $data['lines'][0]['articleCode']);
        // дефолт применился — поле не передавалось
        $this->assertSame('standard', $data['shippingMethod']);
    }

    /**
     * Тот же запрос, отправленный дважды: первый строит и кэширует схему,
     * второй читает её из cache.system (после serialize/unserialize).
     * Поведение обоих запросов должно совпадать — схема переживает round-trip.
     */
    public function testCachedSchemaBehavesAsFreshOne(): void
    {
        $browser = $this->createBrowser();

        $browser->request('POST', '/checkout/order', $this->validPayload());
        $this->assertResponseIsSuccessful();
        $first = $this->getJsonResponse($browser);

        $browser->request('POST', '/checkout/order', $this->validPayload());
        $this->assertResponseIsSuccessful();
        $second = $this->getJsonResponse($browser);

        $this->assertSame($first, $second);
    }

    /**
     * Переименование поля через BindRequestParam: в запросе customer_email,
     * в объекте — email. Отсутствие поля даёт violation именно по имени поля запроса.
     */
    public function testRenamedFieldViolationUsesRequestFieldName(): void
    {
        $payload = $this->validPayload();
        unset($payload['customer_email']);

        $browser = $this->createBrowser();
        $browser->request('POST', '/checkout/order', $payload);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('customer_email', $paths);
    }

    /**
     * Нарушение во вложенном объекте: путь ошибки указывает на вложенное поле.
     */
    public function testNestedObjectViolationHasNestedPath(): void
    {
        $payload = $this->validPayload();
        $payload['deliveryAddress']['zip'] = 'not-a-zip';

        $browser = $this->createBrowser();
        $browser->request('POST', '/checkout/order', $payload);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('deliveryAddress.zip', $paths);
    }

    /**
     * Нарушение в элементе массива объектов: путь содержит индекс.
     */
    public function testArrayItemViolationHasIndexedPath(): void
    {
        $payload = $this->validPayload();
        $payload['lines'][0]['quantity'] = -5;

        $browser = $this->createBrowser();
        $browser->request('POST', '/checkout/order', $payload);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('lines[0].quantity', $paths);
    }

    /**
     * Nullable вложенный объект отсутствует в запросе — свойство становится null,
     * запрос при этом валиден.
     */
    public function testNullableNestedObjectAbsentBecomesNull(): void
    {
        $payload = $this->validPayload();
        unset($payload['giftWrap']);

        $browser = $this->createBrowser();
        $browser->request('POST', '/checkout/order', $payload);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['giftWrap']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'customer_email' => 'buyer@example.com',
            'deliveryAddress' => [
                'country' => 'DE',
                'city' => 'Berlin',
                'zip' => '10115',
                'street' => 'Unter den Linden 1',
            ],
            'lines' => [
                [
                    'sku' => 'ABC-123',
                    'quantity' => 2,
                    'unitPrice' => 19.99,
                ],
                [
                    'sku' => 'XYZ-999',
                    'quantity' => 1,
                    'unitPrice' => 5.0,
                ],
            ],
            'giftWrap' => [
                'style' => 'box',
            ],
        ];
    }
}
