<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class SourceTest extends AbstractWebTestCase
{
    // =========================================================================
    // Источник: query (строка запроса)
    // =========================================================================

    /**
     * Валидные query-параметры: данные корректно извлекаются из строки запроса и передаются в DTO.
     */
    public function testQuerySourceValidParamsReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/query', ['page' => '1', 'limit' => '10']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1, $data['page']);
        $this->assertSame(10, $data['limit']);
    }

    /**
     * Невалидные query-параметры: отрицательные значения нарушают Positive.
     * Violations для обоих полей передаются в контроллер без исключения.
     */
    public function testQuerySourceInvalidParamsReceivesViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/query', ['page' => '-1', 'limit' => '-5']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('page', $paths);
        $this->assertContains('limit', $paths);
    }

    /**
     * Отсутствующие query-параметры: оба поля не переданы.
     * Violations для обоих полей передаются в контроллер.
     */
    public function testQuerySourceMissingParamsReceivesViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/query');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $this->assertNotEmpty($data['errors']);
    }

    // =========================================================================
    // Источник: attributes (параметры роута)
    // =========================================================================

    /**
     * Валидный параметр роута: id корректно извлекается из атрибутов роута и передаётся в DTO.
     */
    public function testAttributesSourceValidIdReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/attributes/42');

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(42, $data['id']);
    }

    /**
     * Невалидный параметр роута: id = 0 нарушает Positive.
     * Бандл должен бросить ValidationFailedException.
     */
    public function testAttributesSourceZeroIdThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request('GET', '/source/attributes/0');
    }

    // =========================================================================
    // Источник: cookies
    // =========================================================================

    /**
     * Валидная cookie: значение session корректно извлекается из cookie и передаётся в DTO.
     */
    public function testCookiesSourceValidSessionReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->getCookieJar()->set(new Cookie('session', 'abc1234567890'));
        $browser->request('GET', '/source/cookies');

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('abc1234567890', $data['session']);
    }

    /**
     * Слишком короткое значение cookie: нарушает Length(min: 10).
     * Violations передаются в контроллер без исключения.
     */
    public function testCookiesSourceShortSessionReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->getCookieJar()->set(new Cookie('session', 'short'));
        $browser->request('GET', '/source/cookies');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('session', $paths);
    }

    /**
     * Cookie отсутствует: поле session не передано в запросе.
     * Violations для session передаются в контроллер.
     */
    public function testCookiesSourceMissingCookieReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/cookies');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('session', $paths);
    }

    // =========================================================================
    // Источник: headers
    // =========================================================================

    /**
     * Валидный заголовок: X-Api-Token корректно извлекается из заголовков и передаётся в DTO.
     */
    public function testHeadersSourceValidTokenReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/source/headers',
            [],
            [],
            [
                'HTTP_X_API_TOKEN' => 'my-secret-token',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('my-secret-token', $data['xApiToken']);
    }

    /**
     * Заголовок отсутствует: X-Api-Token не передан в запросе.
     * Violations для xApiToken передаются в контроллер.
     */
    public function testHeadersSourceMissingTokenReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/headers');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('xApiToken', $paths);
    }

    /**
     * Пустой заголовок: X-Api-Token передан с пустым значением.
     * Нарушает NotBlank, violations передаются в контроллер.
     */
    public function testHeadersSourceEmptyTokenReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/source/headers',
            [],
            [],
            [
                'HTTP_X_API_TOKEN' => '',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('xApiToken', $paths);
    }

    // =========================================================================
    // Источник: server
    // =========================================================================

    /**
     * Валидная серверная переменная: SERVER_USER корректно извлекается и передаётся в DTO.
     */
    public function testServerSourceValidServerNameReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/source/server',
            [],
            [],
            [
                'SERVER_USER' => 'support',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('support', $data['server_user']);
    }

    /**
     * Серверная переменная отсутствует: SERVER_USER не передана.
     * Violations для serverName передаются в контроллер.
     */
    public function testServerSourceMissingServerNameReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/source/server');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('serverUser', $paths);
    }

    // =========================================================================
    // Смешивание источников (BindRequestParam source)
    // =========================================================================

    /**
     * Смешивание источников: id берётся из атрибутов роута, name — из тела
     * POST-запроса. Оба поля валидны.
     */
    public function testMixedSourceValidDataReturnsCorrectObject(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/mixed/7', ['name' => 'Test Name']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(7, $data['id']);
        $this->assertSame('Test Name', $data['name']);
    }

    /**
     * Смешивание источников: id валидный, name отсутствует в теле запроса.
     * Violation только для поля name.
     */
    public function testMixedSourceMissingNameReceivesViolationForNameOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/mixed/7', []);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('name', $paths);
        $this->assertNotContains('id', $paths);
    }

    /**
     * Смешивание источников: id из роута невалидный (0), name валидное.
     * Violation только для поля id.
     */
    public function testMixedSourceInvalidIdReceivesViolationForIdOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/mixed/0', ['name' => 'Test Name']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('id', $paths);
        $this->assertNotContains('name', $paths);
    }

    // =========================================================================
    // BindRequestParam(name: ...) — переопределение имени поля
    // =========================================================================

    /**
     * BindRequestParam(name: 'user_id'): поле в запросе называется user_id,
     * а свойство класса — userId. Данные должны быть корректно смаплены,
     * а валидация успешно пройдена.
     */
    public function testNamedParamValidUserIdReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/named-param', ['user_id' => '42']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(42, $data['user_id']);
    }

    /**
     * BindRequestParam(name: 'user_id'): поле в запросе называется user_id,
     * а свойство класса — userId. Данные должны быть корректно смаплены,
     * но валидация провалена.
     */
    public function testNamedParamInvalidUserIdReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/named-param', ['user_id' => '-24']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('user_id', $paths);
    }

    /**
     * BindRequestParam(name: 'user_id'): поле отсутствует в запросе.
     * Violation для user_id, а не для userId (propertyPath — имя поля в запросе).
     */
    public function testNamedParamMissingFieldReceivesViolationForUserId(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/named-param', []);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('user_id', $paths);
    }

    /**
     * BindRequestParam(name: 'user_id'): поле передано под оригинальным именем
     * свойства (userId), а не под переопределённым (user_id).
     * Бандл должен не найти данные и вернуть violation.
     */
    public function testNamedParamWrongFieldNameReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/source/named-param', ['userId' => '42']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $this->assertNotEmpty($data['errors']);
    }
}
