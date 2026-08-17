<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Symfony\Component\Validator\Exception\ValidationFailedException;

class BasicTest extends AbstractWebTestCase
{
    // =========================================================================
    // Not nullable, атрибут на классе DTO, form-данные
    // =========================================================================

    /**
     * Валидный POST form-запрос: оба поля корректны.
     * Ожидаем успешный ответ с данными из DTO.
     */
    public function testNotNullableValidFormRequestReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/not-nullable',
            [
                'login' => 'user@example.com',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['login']);
        $this->assertSame('supersecret', $data['password']);
    }

    /**
     * Невалидный email: бандл должен бросить ValidationFailedException, а не HTTP-исключение.
     * Исключение должно содержать violation для поля login.
     */
    public function testNotNullableInvalidEmailThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request(
            'POST',
            '/basic/not-nullable',
            [
                'login' => 'not-an-email',
                'password' => 'supersecret',
            ],
        );
    }

    /**
     * Пустой пароль: нарушено ограничение NotBlank для поля password.
     */
    public function testNotNullableBlankPasswordThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request(
            'POST',
            '/basic/not-nullable',
            [
                'login' => 'user@example.com',
                'password' => '',
            ],
        );
    }

    /**
     * Слишком короткий пароль: нарушено ограничение Length(min: 8).
     */
    public function testNotNullableShortPasswordThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request(
            'POST',
            '/basic/not-nullable',
            [
                'login' => 'user@example.com',
                'password' => 'short',
            ],
        );
    }

    /**
     * Одновременно невалидный email и слишком короткий пароль.
     * ValidationFailedException должен содержать ровно 2 violation —
     * по одному на каждое поле (login и password).
     */
    public function testNotNullableMultipleViolationsContainsBothFields(): void
    {
        $browser = $this->createThrowingBrowser();

        try {
            $browser->request(
                'POST',
                '/basic/not-nullable',
                [
                    'login' => 'not-an-email',
                    'password' => 'sh',
                ],
            );
            $this->fail('Ожидалось исключение: ' . ValidationFailedException::class);
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();

            $this->assertCount(2, $violations);

            $paths = [];
            foreach ($violations as $violation) {
                $paths[] = $violation->getPropertyPath();
            }

            $this->assertContains('login', $paths);
            $this->assertContains('password', $paths);
        }
    }

    /**
     * Пустое тело запроса: оба поля отсутствуют.
     * Бандл различает "поле отсутствует" и "поле пустое",
     * поэтому ValidationFailedException должен быть брошен.
     */
    public function testNotNullableMissingAllFieldsThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request('POST', '/basic/not-nullable');
    }

    /**
     * Отсутствует только поле login: violation должен быть именно для login.
     */
    public function testNotNullableMissingLoginViolationIsForLoginField(): void
    {
        $browser = $this->createThrowingBrowser();

        try {
            $browser->request(
                'POST',
                '/basic/not-nullable',
                [
                    'password' => 'supersecret',
                ],
            );
            $this->fail('Ожидалось исключение ValidationFailedException');
        } catch (ValidationFailedException $e) {
            $paths = [];
            foreach ($e->getViolations() as $violation) {
                $paths[] = $violation->getPropertyPath();
            }

            $this->assertContains('login', $paths);
            $this->assertNotContains('password', $paths);
        }
    }

    // =========================================================================
    // Not nullable, JSON-формат
    // =========================================================================

    /**
     * Валидный POST JSON-запрос: оба поля корректны.
     * Бандл должен корректно разобрать JSON и вернуть данные.
     */
    public function testNotNullableValidJsonRequestReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/not-nullable',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(['login' => 'user@example.com', 'password' => 'supersecret']),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['login']);
        $this->assertSame('supersecret', $data['password']);
    }

    /**
     * Невалидный JSON-запрос: email некорректен, пароль слишком короткий.
     * ValidationFailedException должен быть брошен независимо от формата данных.
     */
    public function testNotNullableInvalidJsonThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request(
            'POST',
            '/basic/not-nullable',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(['login' => 'not-an-email', 'password' => 'sh']),
        );
    }

    /**
     * JSON с отсутствующими полями: бандл должен определить, что поля нет,
     * и выбросить ValidationFailedException.
     */
    public function testNotNullableJsonMissingFieldsThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request(
            'POST',
            '/basic/not-nullable',
            [],
            [],
            $this->jsonServerVars(),
            json_encode([]),
        );
    }

    // =========================================================================
    // Nullable, атрибут на аргументе контроллера
    // =========================================================================

    /**
     * Nullable + валидный POST-запрос: объект должен быть собран и передан.
     */
    public function testNullableWithValidDataReturnsObject(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/nullable',
            [
                'login' => 'user@example.com',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['login']);
        $this->assertSame('supersecret', $data['password']);
    }

    /**
     * Nullable + GET-запрос без данных: в контроллер передаётся null.
     * Это позволяет одним экшном обслуживать отображение и обработку формы.
     */
    public function testNullableGetRequestWithoutDataReceivesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/basic/nullable');

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('empty', $data['result']);
    }

    /**
     * Nullable + POST-запрос с пустым телом: в контроллер передаётся null.
     */
    public function testNullablePostWithEmptyBodyReceivesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/basic/nullable', []);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('empty', $data['result']);
    }

    /**
     * Nullable + невалидные данные: несмотря на nullable-аргумент,
     * при наличии данных с ошибками бандл должен бросить ValidationFailedException.
     */
    public function testNullableWithInvalidDataThrowsValidationFailedException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidationFailedException::class);

        $browser->request(
            'POST',
            '/basic/nullable',
            [
                'login' => 'not-an-email',
                'password' => '123',
            ],
        );
    }

    // =========================================================================
    // Violations nullable в контроллер — исключение не бросается
    // =========================================================================

    /**
     * Violations nullable + валидные данные: $violations должен быть null
     * (валидация прошла успешно), а объект — собран корректно.
     */
    public function testViolationsNullableValidDataReceivesNullViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/violations-nullable',
            [
                'login' => 'user@example.com',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['login']);
        $this->assertArrayNotHasKey('errors', $data);
    }

    /**
     * Violations nullable + оба поля невалидны: исключение не бросается,
     * violations содержит ровно 2 нарушения для login и password.
     */
    public function testViolationsNullableInvalidDataReceivesTwoViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/violations-nullable',
            [
                'login' => 'not-an-email',
                'password' => 'sh',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $this->assertCount(2, $data['errors']);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('login', $paths);
        $this->assertContains('password', $paths);
    }

    /**
     * Violations nullable + только невалидный email: violation только для login,
     * поле password не содержит нарушений.
     */
    public function testViolationsNullableInvalidEmailReceivesOnlyLoginViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/violations-nullable',
            [
                'login' => 'not-an-email',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $this->assertCount(1, $data['errors']);
        $this->assertSame('login', $data['errors'][0]['property']);
    }

    /**
     * Violations nullable + только пустой login: violation только для login.
     */
    public function testViolationsNullableBlankLoginReceivesOnlyLoginViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/violations-nullable',
            [
                'login' => '',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('login', $paths);
        $this->assertNotContains('password', $paths);
    }

    // =========================================================================
    // Violations not nullable в контроллер — пустой список при успехе
    // =========================================================================

    /**
     * Violations not nullable + валидные данные: violations не null,
     * а пустой объект ConstraintViolationList (count = 0).
     */
    public function testViolationsNotNullableValidDataReceivesEmptyList(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/violations-not-nullable',
            [
                'login' => 'user@example.com',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(0, $data['count']);
        $this->assertSame([], $data['errors']);
    }

    /**
     * Violations not nullable + невалидные данные: список нарушений содержит
     * именно 2 элемента, исключение не бросается.
     */
    public function testViolationsNotNullableInvalidDataReceivesTwoViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/violations-not-nullable',
            [
                'login' => 'not-an-email',
                'password' => 'sh',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(2, $data['count']);
    }

    // =========================================================================
    // Несколько ValidRequest-аргументов подряд
    // =========================================================================

    /**
     * Два ValidRequest-аргумента, оба валидны: violations для каждого = 0.
     * Порядок аргументов важен — первый violations относится к первому объекту.
     */
    public function testMultipleValidRequestsBothValidReceiveZeroViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/basic/multiple',
            [
                'login' => 'user@example.com',
                'password' => 'supersecret',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(0, $data['first_errors']);
        $this->assertSame(0, $data['second_errors']);
    }
}
