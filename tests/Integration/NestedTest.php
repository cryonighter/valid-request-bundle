<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class NestedTest extends AbstractWebTestCase
{
    // =========================================================================
    // Вспомогательные методы для построения тестовых данных
    // =========================================================================

    private function validContact(string $type = 'email', string $value = 'user@example.com'): array
    {
        return ['type' => $type, 'value' => $value];
    }

    // =========================================================================
    // Полностью валидные запросы
    // =========================================================================

    /**
     * Все поля валидны, settings заполнен, contacts содержит один элемент.
     * Объект должен быть корректно собран со вложенными объектами.
     */
    public function testValidRequestWithSettingsAndOneContactReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'settings' => [
                    'itemsPerPage' => '20',
                    'theme' => 'dark',
                ],
                'contacts' => [$this->validContact()],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('john', $data['username']);
        $this->assertSame(20, $data['settings']['items_per_page']);
        $this->assertSame('dark', $data['settings']['theme']);
        $this->assertCount(1, $data['contacts']);
        $this->assertSame('email', $data['contacts'][0]['type']);
        $this->assertSame('user@example.com', $data['contacts'][0]['value']);
    }

    /**
     * Несколько контактов разных типов: email и phone.
     * Все объекты массива должны быть корректно собраны.
     */
    public function testValidRequestWithMultipleContactsReturnsAllContacts(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'settings' => [
                    'itemsPerPage' => '20',
                    'theme' => 'dark',
                ],
                'contacts' => [
                    $this->validContact('email', 'user@example.com'),
                    $this->validContact('phone', '+79001234567'),
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertCount(2, $data['contacts']);
        $this->assertSame('email', $data['contacts'][0]['type']);
        $this->assertSame('phone', $data['contacts'][1]['type']);
    }

    // =========================================================================
    // Nullable вложенный объект
    // =========================================================================

    /**
     * settings — nullable: если данные для settings не переданы,
     * свойство должно получить null, а не вызвать ошибку.
     */
    public function testNullableNestedObjectReceivesNullWhenNotProvided(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [$this->validContact()],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['settings']);
    }

    /**
     * settings — nullable: если данные для settings переданы в виде пустого массива,
     * а не null, вложенный объект должен быть провалидирован и вернуть violations.
     */
    public function testNullableNestedObjectWithEmptyDataReceivesViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'settings' => [
                ],
                'contacts' => [$this->validContact()],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('settings.theme', $paths);
        $this->assertContains('settings.itemsPerPage', $paths);
    }

    /**
     * settings — nullable: если данные для settings переданы частично,
     * вложенный объект должен быть провалидирован и вернуть violations.
     */
    public function testNullableNestedObjectWithInvalidDataReceivesViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'settings' => [
                    'theme' => 'dark',
                ],
                'contacts' => [$this->validContact()],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('settings.itemsPerPage', $paths);
    }

    /**
     * settings — nullable: если переданы данные только для вложенного объекта,
     * но все поля settings пустые — возникают violations для полей settings.
     */
    public function testNullableNestedObjectWithBlankFieldsReceivesViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'settings' => [
                    'itemsPerPage' => '',
                    'theme' => '',
                ],
                'contacts' => [$this->validContact()],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertNotEmpty(array_filter($paths, fn($p) => str_contains($p, 'settings')));
    }

    // =========================================================================
    // Массив объектов (contacts)
    // =========================================================================

    /**
     * Пустой массив contacts: нарушает NotBlank на поле contacts.
     */
    public function testEmptyContactsArrayReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('contacts', $paths);
    }

    /**
     * Массив contacts отсутствует в запросе: нарушает NotBlank.
     */
    public function testMissingContactsArrayReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('contacts', $paths);
    }

    /**
     * Один из элементов массива contacts имеет невалидный type (не из choices).
     * Violation должен указывать на конкретный элемент массива.
     */
    public function testContactWithInvalidTypeReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    ['type' => 'invalid-type', 'value' => 'user@example.com'],
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('contacts[0].type', $paths);
    }

    /**
     * Первый элемент массива валиден, второй — нет.
     * Violation должен быть только для второго элемента.
     */
    public function testSecondContactInvalidReceivesViolationForSecondOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    $this->validContact('email', 'user@example.com'),
                    ['type' => 'fax', 'value' => 'ab'],
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        // Violation должен указывать на второй элемент (индекс [1])
        $this->assertContains('contacts[1].type', $paths);
        $this->assertContains('contacts[1].value', $paths);
        $this->assertNotEmpty(array_filter($paths, fn($p) => str_contains($p, '[1]')));

        // Первый элемент валиден — путей с [0] быть не должно
        $this->assertNotContains('contacts[0].type', $paths);
        $this->assertNotContains('contacts[0].value', $paths);
    }

    /**
     * Элемент contacts — пустой объект (все поля отсутствуют).
     * Violations для type и value внутри элемента массива.
     */
    public function testContactWithAllBlankFieldsReceivesViolationsForBothFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    ['type' => '', 'value' => ''],
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('contacts[0].type', $paths);
        $this->assertContains('contacts[0].value', $paths);
    }

    // =========================================================================
    // Верхний уровень — поле username
    // =========================================================================

    /**
     * Отсутствует поле username на верхнем уровне.
     * Violation для username, contacts при этом валидные.
     */
    public function testMissingUsernameReceivesViolationForUsernameOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'contacts' => [$this->validContact()],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('username', $paths);
        $this->assertNotContains('contacts', $paths);
        $this->assertNotContains('contacts[0].type', $paths);
        $this->assertNotContains('contacts[0].value', $paths);
    }

    /**
     * JSON-формат: вложенные объекты и массивы объектов
     * корректно разбираются из JSON-тела запроса.
     */
    public function testValidJsonRequestWithNestedObjectsReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'username' => 'john',
                    'settings' => ['itemsPerPage' => 20, 'theme' => 'dark'],
                    'contacts' => [
                        ['type' => 'email', 'value' => 'user@example.com'],
                    ],
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('john', $data['username']);
        $this->assertSame(20, $data['settings']['items_per_page']);
        $this->assertSame('dark', $data['settings']['theme']);
        $this->assertArrayHasKey('contacts', $data);
        $this->assertCount(1, $data['contacts']);
        $this->assertSame('email', $data['contacts'][0]['type']);
        $this->assertSame('user@example.com', $data['contacts'][0]['value']);
    }
}
