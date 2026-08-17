<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class GroupsTest extends AbstractWebTestCase
{
    // =========================================================================
    // CountryRequest: BindRequestParam(source, groups) — источник зависит от группы
    // =========================================================================

    /**
     * Группа Create: code берётся из тела запроса (payload), т.к. переопределение
     * источника через BindRequestParam действует только для группы Update.
     * Оба поля валидны — объект собирается корректно.
     */
    public function testCreateGroupTakesCodeFromPayload(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/country',
            [
                'code' => 'DE',
                'name' => 'Germany',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('DE', $data['code']);
        $this->assertSame('Germany', $data['name']);
    }

    /**
     * Группа Create: code отсутствует в теле запроса.
     * Violation для поля code.
     */
    public function testCreateGroupMissingCodeReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/country',
            [
                'name' => 'Germany',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('code', $paths);
    }

    /**
     * Группа Create: невалидный код страны в теле запроса.
     * Violation для поля code (Assert\Country).
     */
    public function testCreateGroupInvalidCountryCodeReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/country',
            [
                'code' => 'XX',
                'name' => 'Germany',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('code', $paths);
    }

    /**
     * Группа Update: code берётся из атрибутов роута ({code}), т.к.
     * BindRequestParam(source: attributes, groups: ['Update']) совпадает с текущей группой.
     * name берётся из тела PUT-запроса.
     */
    public function testUpdateGroupTakesCodeFromRouteAttribute(): void
    {
        $browser = $this->createBrowser();
        $browser->request('PUT', '/groups/country/DE', ['name' => 'Germany']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('DE', $data['code']);
        $this->assertSame('Germany', $data['name']);
    }

    /**
     * Группа Update: невалидный код страны в атрибуте роута.
     * Violation для поля code.
     */
    public function testUpdateGroupInvalidCodeInRouteReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('PUT', '/groups/country/XX', ['name' => 'Germany']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('code', $paths);
    }

    /**
     * Группа Update: name отсутствует в теле запроса.
     * Violation для поля name, поле code из роута валидное.
     */
    public function testUpdateGroupMissingNameReceivesViolationForNameOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('PUT', '/groups/country/DE');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('name', $paths);
        $this->assertNotContains('code', $paths);
    }

    // =========================================================================
    // GroupedRequest: валидация только нужных полей в зависимости от группы
    // =========================================================================

    /**
     * Группа Default: поле phone не валидируется (его NotBlank в группе Extra).
     * Пустой phone не вызывает violation — только поля Default-группы проверяются.
     */
    public function testDefaultGroupDoesNotValidateExtraGroupFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/default',
            [
                'email' => 'user@example.com',
                'phone' => '',
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['email']);
    }

    /**
     * Группа Default: невалидный email вызывает violation для поля email.
     */
    public function testDefaultGroupValidatesEmailField(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/default',
            [
                'email' => 'not-an-email',
                'phone' => '',
                'name' => 'John',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('email', $paths);
        $this->assertNotContains('phone', $paths);
    }

    /**
     * Группа Extra: поле email не валидируется (его NotBlank и Email в группе Default).
     * Невалидный email не вызывает violation — только поля Extra-группы проверяются.
     */
    public function testExtraGroupDoesNotValidateDefaultGroupFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/extra',
            [
                'email' => 'not-an-email',
                'phone' => '+79001234567',
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('+79001234567', $data['phone']);
    }

    /**
     * Группа Extra: пустой phone вызывает violation только для поля phone.
     * Email не валидируется, даже если он невалидный.
     */
    public function testExtraGroupValidatesPhoneField(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/extra',
            [
                'email' => 'not-an-email',
                'phone' => '',
                'name' => 'John',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('phone', $paths);
        $this->assertNotContains('email', $paths);
    }

    /**
     * Группы Default + Extra: валидируются поля из обеих групп.
     * Невалидный email и phone, оба вызывают violations.
     */
    public function testBothGroupsValidateAllFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/both',
            [
                'email' => 'not-an-email',
                'phone' => 'not-an-phone',
                'name' => 'John',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('email', $paths);
        $this->assertContains('phone', $paths);
    }

    /**
     * Группы Default + Extra: все поля валидны — объект собирается корректно.
     */
    public function testBothGroupsValidDataReturnsObject(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/both',
            [
                'email' => 'user@example.com',
                'phone' => '+79001234567',
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['email']);
        $this->assertSame('+79001234567', $data['phone']);
        $this->assertSame('John', $data['name']);
    }

    /**
     * Группы Default + Extra: поле name валидируется в обеих группах.
     * Слишком короткое name вызывает violation независимо от набора групп.
     */
    public function testBothGroupsShortNameReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/groups/grouped/both',
            [
                'email' => 'user@example.com',
                'phone' => '+79001234567',
                'name' => 'J',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('name', $paths);
    }
}
