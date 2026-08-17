<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class ExtraFieldsTest extends AbstractWebTestCase
{
    // =========================================================================
    // payload: лишние поля автоматически получают violation
    // =========================================================================

    /**
     * Только объявленные поля в payload: бандл не находит лишних полей,
     * объект успешно собирается без violation.
     */
    public function testPayloadWithOnlyDeclaredFieldsReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('John', $data['name']);
        $this->assertSame('john@example.com', $data['email']);
    }

    /**
     * Одно лишнее поле в payload: бандл автоматически применяет
     * NoExtraFields к этому полю, violation содержит его имя.
     */
    public function testPayloadWithOneExtraFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [
                'name' => 'John',
                'email' => 'john@example.com',
                'extra_field' => 'unexpected',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('extra_field', $paths);
        $this->assertNotContains('name', $paths);
        $this->assertNotContains('email', $paths);
    }

    /**
     * Несколько лишних полей в payload: violation возникает
     * для каждого необъявленного поля отдельно.
     */
    public function testPayloadWithMultipleExtraFieldsReceivesViolationForEach(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [
                'name' => 'John',
                'email' => 'john@example.com',
                'extra_1' => 'unexpected',
                'extra_2' => 'also_unexpected',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('extra_1', $paths);
        $this->assertContains('extra_2', $paths);
    }

    /**
     * Лишнее поле и одновременно отсутствует поле email:
     * violations возникают и для лишнего поля, и для отсутствующего email.
     */
    public function testPayloadWithExtraFieldAndInvalidEmailReceivesBothViolations(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [
                'name' => 'John',
                'email' => '',
                'extra_field' => 'unexpected',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('extra_field', $paths);
        $this->assertContains('email', $paths);
    }

    /**
     * Только лишние поля, объявленные поля отсутствуют:
     * violations и для лишних полей, и для отсутствующих объявленных.
     */
    public function testPayloadWithOnlyExtraFieldsReceivesViolationsForAllProblems(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [
                'unexpected' => 'value',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('unexpected', $paths);
        $this->assertContains('name', $paths);
        $this->assertContains('email', $paths);
    }

    /**
     * JSON payload с лишним полем: NoExtraFields применяется
     * независимо от формата данных, violation возникает.
     */
    public function testJsonPayloadWithExtraFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                    'email' => 'john@example.com',
                    'extra_field' => 'unexpected',
                ],
            ),
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('extra_field', $paths);
    }

    /**
     * JSON payload только с объявленными полями: violation не возникает.
     */
    public function testJsonPayloadWithOnlyDeclaredFieldsReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/extra-fields/payload',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('John', $data['name']);
    }

    // =========================================================================
    // query: лишние параметры НЕ вызывают violation
    // =========================================================================

    /**
     * Только объявленный query-параметр: объект успешно собирается.
     */
    public function testQueryWithOnlyDeclaredParamReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request('GET', '/extra-fields/query', ['page' => '1']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1, $data['page']);
    }

    /**
     * Лишний query-параметр: бандл не применяет NoExtraFields к источнику query,
     * violation не возникает, объект успешно собирается.
     */
    public function testQueryWithExtraParamDoesNotReceiveViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/extra-fields/query',
            [
                'page' => '1',
                'extra' => 'unexpected',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1, $data['page']);

        $this->assertArrayNotHasKey('errors', $data);
    }

    /**
     * Несколько лишних query-параметров: все игнорируются,
     * violation не возникает ни для одного из них.
     */
    public function testQueryWithMultipleExtraParamsDoesNotReceiveViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'GET',
            '/extra-fields/query',
            [
                'page' => '1',
                'extra_1' => 'unexpected',
                'extra_2' => 'also_unexpected',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1, $data['page']);
        $this->assertArrayNotHasKey('errors', $data);
    }
}
