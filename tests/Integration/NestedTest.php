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

    // =========================================================================
    // Ассоциативный массив объектов (contacts с произвольными строковыми ключами)
    // =========================================================================

    /**
     * contacts передан как ассоциативный массив с произвольными строковыми ключами.
     * Каждый элемент — валидный объект ContactDTO.
     * Строковые ключи должны сохраниться в собранном массиве объектов.
     */
    public function testAssociativeContactsPreserveStringKeys(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    'home' => $this->validContact('email', 'home@example.com'),
                    'work' => $this->validContact('phone', '+79001234567'),
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertCount(2, $data['contacts']);
        $this->assertArrayHasKey('home', $data['contacts']);
        $this->assertArrayHasKey('work', $data['contacts']);

        $this->assertSame('email', $data['contacts']['home']['type']);
        $this->assertSame('home@example.com', $data['contacts']['home']['value']);

        $this->assertSame('phone', $data['contacts']['work']['type']);
        $this->assertSame('+79001234567', $data['contacts']['work']['value']);
    }

    /**
     * Смешанные ключи: часть элементов с числовыми ключами, часть — со строковыми.
     * Все ключи должны сохраниться без переиндексации.
     */
    public function testMixedKeysContactsPreserveAllKeys(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    0 => $this->validContact('email', 'user@example.com'),
                    'backup' => $this->validContact('phone', '+79001234567'),
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertCount(2, $data['contacts']);
        $this->assertArrayHasKey(0, $data['contacts']);
        $this->assertArrayHasKey('backup', $data['contacts']);

        $this->assertSame('email', $data['contacts'][0]['type']);
        $this->assertSame('phone', $data['contacts']['backup']['type']);
    }

    /**
     * Один из элементов ассоциативного массива невалиден.
     * Путь ошибки должен указывать на конкретный строковый ключ.
     */
    public function testAssociativeContactWithInvalidElementReceivesViolationForThatKey(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    'home' => $this->validContact('email', 'home@example.com'),
                    'work' => ['type' => 'fax', 'value' => 'ab'],
                    // TODO: лишнее поле, другое лишнее поле, отсутствующие поля, все с привязкой к ключу
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        // Violation должен указывать на элемент с ключом "work"
        $this->assertContains('contacts[work].type', $paths);
        $this->assertContains('contacts[work].value', $paths);

        // Валидный элемент "home" не должен давать ошибок
        $this->assertNotContains('contacts[home].type', $paths);
        $this->assertNotContains('contacts[home].value', $paths);
    }

    /**
     * Элемент ассоциативного массива со всеми пустыми полями.
     * Violations для type и value привязаны к строковому ключу.
     */
    public function testAssociativeContactWithAllBlankFieldsReceivesViolationsForThatKey(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/nested/profile',
            [
                'username' => 'john',
                'contacts' => [
                    'primary' => ['type' => '', 'value' => ''],
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('contacts[primary].type', $paths);
        $this->assertContains('contacts[primary].value', $paths);
    }

    /**
     * JSON-формат: ассоциативный массив объектов с произвольными строковыми ключами
     * корректно разбирается из JSON-тела запроса, ключи сохраняются.
     */
    public function testValidJsonRequestWithAssociativeContactsPreservesKeys(): void
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
                    'contacts' => [
                        'home' => ['type' => 'email', 'value' => 'home@example.com'],
                        'work' => ['type' => 'phone', 'value' => '+79001234567'],
                    ],
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertCount(2, $data['contacts']);

        $this->assertArrayHasKey('home', $data['contacts']);
        $this->assertArrayHasKey('work', $data['contacts']);

        $this->assertSame('email', $data['contacts']['home']['type']);
        $this->assertSame('phone', $data['contacts']['work']['type']);
    }
}
