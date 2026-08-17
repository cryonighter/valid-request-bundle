<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class DefaultValueTest extends AbstractWebTestCase
{
    // =========================================================================
    // Поле отсутствует в запросе — применяется значение по умолчанию
    // =========================================================================

    /**
     * Все поля со значениями по умолчанию отсутствуют в запросе:
     * каждое свойство принимает своё значение по умолчанию.
     */
    public function testMissingFieldsReceiveDefaultValues(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('John', $data['name']);
        $this->assertSame('user', $data['role']);
        $this->assertSame('default comment', $data['comment']);
        $this->assertSame(5, $data['priority']);
    }

    /**
     * Поле role отсутствует в запросе: принимает значение по умолчанию 'user'.
     * Остальные поля переданы явно.
     */
    public function testMissingStringFieldReceivesDefaultValue(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
                'comment' => 'my comment',
                'priority' => '3',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user', $data['role']);
    }

    /**
     * Поле priority отсутствует в запросе: принимает значение по умолчанию 5.
     */
    public function testMissingIntFieldReceivesDefaultValue(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
                'role' => 'admin',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(5, $data['priority']);
    }

    /**
     * Поле nullable comment отсутствует в запросе:
     * принимает значение по умолчанию 'default comment', а не null.
     */
    public function testMissingNullableFieldReceivesDefaultValueNotNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('default comment', $data['comment']);
    }

    // =========================================================================
    // Поле передано явно — значение по умолчанию игнорируется
    // =========================================================================

    /**
     * Все поля переданы явно: значения по умолчанию игнорируются,
     * свойства принимают переданные значения.
     */
    public function testAllFieldsProvidedExplicitlyOverrideDefaults(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
                'role' => 'admin',
                'comment' => 'my comment',
                'priority' => '10',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('admin', $data['role']);
        $this->assertSame('my comment', $data['comment']);
        $this->assertSame(10, $data['priority']);
    }

    // =========================================================================
    // form: поле определено без значения — nullable свойство получает null
    // =========================================================================

    /**
     * Form-запрос: поле "comment" присутствует в запросе, но передано без значения (пустая строка в form).
     * Так как свойство "comment" nullable — значение должно быть null, а не значение по умолчанию 'default comment'.
     * Поля "role" и "priority" в запросе отсутствуют полностью и должны получить значения по умолчанию 'user' и 5 соответственно.
     */
    public function testFormNullableFieldPresentWithEmptyValueReceivesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
                'comment' => '',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['comment']);
    }

    // =========================================================================
    // form: поле передано с явной пустой строкой — string свойство получает ''
    // =========================================================================

    /**
     * Form-запрос: поле "role" присутствует в запросе, но передано без значения (пустая строка в form).
     * Так как свойство "role" not nullable — значение должно быть '', а не значение по умолчанию 'user'.
     */
    public function testFormStringFieldPresentWithEmptyStringReceivesEmptyString(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
                'role' => '',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('', $data['role']);
    }

    // =========================================================================
    // JSON: явный null — nullable свойство получает null
    // =========================================================================

    /**
     * JSON-запрос: поле comment передано с явным null.
     * Свойство nullable должно получить null, а не значение по умолчанию 'default comment'.
     */
    public function testJsonNullableFieldExplicitNullReceivesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                    'comment' => null,
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['comment']);
    }

    /**
     * JSON-запрос: поле comment отсутствует в теле запроса.
     * Свойство nullable должно получить значение по умолчанию 'default comment'.
     */
    public function testJsonNullableFieldMissingReceivesDefaultValue(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('default comment', $data['comment']);
    }

    // =========================================================================
    // JSON: явная пустая строка — string свойство получает ''
    // =========================================================================

    /**
     * JSON-запрос: поле role передано с явной пустой строкой.
     * Свойство string должно получить '', а не значение по умолчанию 'user'.
     */
    public function testJsonStringFieldExplicitEmptyStringReceivesEmptyString(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                    'role' => '',
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('', $data['role']);
    }

    /**
     * JSON-запрос: поле role отсутствует в теле запроса.
     * Свойство string должно получить значение по умолчанию 'user'.
     */
    public function testJsonStringFieldMissingReceivesDefaultValue(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user', $data['role']);
    }

    // =========================================================================
    // Граничные случаи: комбинации отсутствующих и явных полей
    // =========================================================================

    /**
     * JSON-запрос: одни поля отсутствуют (получают дефолт),
     * другие переданы с явными значениями (дефолт игнорируется),
     * третьи переданы как null (получают null).
     */
    public function testJsonMixOfMissingExplicitAndNullFieldsBehavesCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                    'role' => 'moderator', // явное значение — дефолт игнорируется
                    'comment' => null,        // явный null — дефолт игнорируется
                    // priority отсутствует — получает дефолт 5
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('moderator', $data['role']);
        $this->assertNull($data['comment']);
        $this->assertSame(5, $data['priority']);
    }

    /**
     * Form-запрос: комбинация — одно поле отсутствует (получает дефолт),
     * другое передано с пустой строкой (получает '').
     */
    public function testFormMixOfMissingAndEmptyStringFieldsBehavesCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value',
            [
                'name' => 'John',
                'role' => '',    // явная пустая строка — дефолт игнорируется
                // priority отсутствует — получает дефолт 5
                // comment отсутствует — получает дефолт 'default comment'
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('', $data['role']);
        $this->assertSame(5, $data['priority']);
        $this->assertSame('default comment', $data['comment']);
    }
}
