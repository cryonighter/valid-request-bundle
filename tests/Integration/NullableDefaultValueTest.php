<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Когда nullable-аргумент контроллера получает null, если у DTO есть
 * и обязательные поля, и поля со значениями по умолчанию.
 *
 * Значения по умолчанию не считаются входными данными: если в запросе
 * ничего нет, аргумент получает null без валидации. Если пришло хотя бы
 * одно поле, данные считаются вводом и валидируются полностью.
 */
class NullableDefaultValueTest extends AbstractWebTestCase
{
    // =========================================================================
    // Входных данных нет — nullable-аргумент получает null без валидации
    // =========================================================================

    /**
     * GET-запрос без данных (например, отрисовка формы):
     * значения по умолчанию не уходят в валидацию, аргумент получает null.
     * Сценарий бага: раньше здесь возвращалась ошибка NotBlank на name.
     */
    public function testGetWithoutDataReturnsNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/default-value/nullable');

        $this->assertNullResult($browser);
    }

    /**
     * POST-запрос с пустой формой: аргумент получает null.
     */
    public function testPostWithEmptyFormReturnsNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/default-value/nullable');

        $this->assertNullResult($browser);
    }

    /**
     * POST-запрос с пустым JSON-объектом: аргумент получает null.
     */
    public function testPostWithEmptyJsonObjectReturnsNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value/nullable',
            [],
            [],
            $this->jsonServerVars(),
            '{}',
        );

        $this->assertNullResult($browser);
    }

    /**
     * В query есть параметры, которых нет в DTO.
     * Входными данными они не считаются, аргумент получает null.
     */
    public function testUnrelatedQueryParamsDoNotCountAsInput(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/default-value/nullable', ['utm_source' => 'newsletter']);

        $this->assertNullResult($browser);
    }

    // =========================================================================
    // Входные данные есть — данные валидируются, null не возвращается
    // =========================================================================

    /**
     * Передано только необязательное поле (со значением по умолчанию).
     * Это ввод: данные валидируются, и отсутствие обязательного name даёт ошибку.
     */
    public function testOnlyOptionalFieldProvidedIsValidated(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/default-value/nullable', ['role' => 'admin']);

        $this->assertViolationsContain($browser, 'name');
    }

    /**
     * Переданы значения, совпадающие со значениями по умолчанию.
     * Отличить их от «ничего не прислали» по самим данным нельзя,
     * но это ввод, поэтому данные должны пройти валидацию.
     */
    public function testValuesEqualToDefaultsAreTreatedAsInput(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value/nullable',
            [
                'role' => 'user',
                'priority' => '5',
            ],
        );

        $this->assertViolationsContain($browser, 'name');
    }

    /**
     * Обязательное поле пришло пустым.
     * Пустое поле тоже ввод: ожидается ошибка NotBlank, а не null.
     */
    public function testRequiredFieldPresentButEmptyIsValidated(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/default-value/nullable', ['name' => '']);

        $this->assertViolationsContain($browser, 'name');
    }

    /**
     * JSON-запрос, в котором nullable-поле передано явным null.
     * Явный null тоже ввод, поэтому данные валидируются.
     */
    public function testJsonExplicitNullIsTreatedAsInput(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value/nullable',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(['comment' => null]),
        );

        $this->assertViolationsContain($browser, 'name');
    }

    /**
     * Основной источник (тело запроса) пуст, но поле пришло из другого
     * источника через BindRequestParam (query). Это ввод, null не возвращается.
     */
    public function testFieldFromAlternativeSourceIsTreatedAsInput(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/default-value/nullable', ['page' => '2']);

        $this->assertViolationsContain($browser, 'name');
    }

    // =========================================================================
    // Валидные данные — объект собирается, значения по умолчанию подставляются
    // =========================================================================

    /**
     * Передано только обязательное поле: объект собирается,
     * остальные свойства получают значения по умолчанию.
     */
    public function testValidDataBuildsObjectWithDefaults(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/default-value/nullable', ['name' => 'John']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['violations']);
        $this->assertSame(
            [
                'name' => 'John',
                'role' => 'user',
                'comment' => 'default comment',
                'priority' => 5,
                'page' => 1,
            ],
            $data['dto'],
        );
    }

    /**
     * Данные пришли из обоих источников: тела запроса и query.
     */
    public function testValidDataFromBothSourcesBuildsObject(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/default-value/nullable?page=3', ['name' => 'John']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['violations']);
        $this->assertSame('John', $data['dto']['name']);
        $this->assertSame(3, $data['dto']['page']);
    }

    // =========================================================================
    // Not nullable аргумент — null недопустим, данные всегда валидируются
    // =========================================================================

    /**
     * Not nullable аргумент без входных данных: возвращать null нельзя,
     * поэтому данные из значений по умолчанию валидируются и дают ошибку на name.
     */
    public function testNonNullableArgumentWithoutDataIsValidated(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/default-value/required');

        $this->assertViolationsContain($browser, 'name');
    }

    // =========================================================================
    // Патчер свойства: ввод только тогда, когда он вернул не null
    // =========================================================================

    /**
     * Пустой запрос, патчер свойства вернул null:
     * это не ввод, аргумент получает null без валидации.
     */
    public function testPropertyPatcherReturningNullKeepsNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/default-value/property-patcher');

        $this->assertNullResult($browser);
    }

    /**
     * Тело запроса пустое, но патчер свойства взял значение из заголовка.
     * Это ввод: данные валидируются, и отсутствие name даёт ошибку.
     */
    public function testPropertyPatcherReturningValueIsTreatedAsInput(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/default-value/property-patcher',
            [],
            [],
            ['HTTP_X_REQUEST_ID' => 'req-42'],
        );

        $this->assertViolationsContain($browser, 'name');
    }

    /**
     * Валидные данные и значение от патчера свойства: объект собирается,
     * свойство получает значение, которое вернул патчер.
     */
    public function testPropertyPatcherValueIsHydrated(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value/property-patcher',
            ['name' => 'John'],
            [],
            ['HTTP_X_REQUEST_ID' => 'req-42'],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['violations']);
        $this->assertSame(
            [
                'name' => 'John',
                'role' => 'user',
                'requestId' => 'req-42',
            ],
            $data['dto'],
        );
    }

    // =========================================================================
    // Патчер объекта: ввод только тогда, когда он изменил данные
    // =========================================================================

    /**
     * Пустой запрос, патчер объекта вернул данные без изменений:
     * это не ввод, аргумент получает null без валидации.
     */
    public function testObjectPatcherWithoutChangesKeepsNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/default-value/object-patcher');

        $this->assertNullResult($browser);
    }

    /**
     * Тело запроса пустое, но патчер объекта изменил данные (взял channel из заголовка).
     * Это ввод: данные валидируются, и отсутствие name даёт ошибку.
     */
    public function testObjectPatcherChangingDataIsTreatedAsInput(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/default-value/object-patcher',
            [],
            [],
            ['HTTP_X_CHANNEL' => 'mobile'],
        );

        $this->assertViolationsContain($browser, 'name');
    }

    /**
     * Валидные данные и изменения от патчера объекта: объект собирается
     * из данных, которые вернул патчер.
     */
    public function testObjectPatcherResultIsHydrated(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/default-value/object-patcher',
            ['name' => 'John'],
            [],
            ['HTTP_X_CHANNEL' => 'mobile'],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['violations']);
        $this->assertSame(
            [
                'name' => 'John',
                'role' => 'user',
                'channel' => 'mobile',
            ],
            $data['dto'],
        );
    }

    // =========================================================================
    // Вспомогательные методы
    // =========================================================================

    /**
     * Проверяет, что в контроллер пришли null вместо DTO и null вместо списка ошибок.
     */
    private function assertNullResult(KernelBrowser $browser): void
    {
        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['dto'], 'Ожидался null вместо DTO');
        $this->assertNull($data['violations'], 'Валидация не должна была выполняться');
    }

    /**
     * Проверяет, что валидация выполнялась и среди ошибок есть ошибка по указанному полю.
     */
    private function assertViolationsContain(KernelBrowser $browser, string $field): void
    {
        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['dto']);
        $this->assertIsArray($data['violations'], 'Ожидались ошибки валидации');
        $this->assertContains($field, $data['violations']);
    }
}
