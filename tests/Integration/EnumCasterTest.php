<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\Enum\OrderStatus;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Enum\Priority;

class EnumCasterTest extends AbstractWebTestCase
{
    // =========================================================================
    // String-backed enum
    // =========================================================================

    /**
     * Валидное строковое значение backed enum приводится к case.
     */
    public function testStringEnumValidValueIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum', ['status' => 'paid']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(OrderStatus::Paid->value, $data['status']);
    }

    /**
     * Значение вне набора string enum не приводится и вызывает нарушение.
     */
    public function testStringEnumInvalidValueReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum', ['status' => 'unknown']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('status', $paths);
    }

    /**
     * Отсутствие обязательного enum вызывает нарушение NotNull.
     */
    public function testMissingRequiredEnumReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('status', $paths);
    }

    // =========================================================================
    // Int-backed enum
    // =========================================================================

    /**
     * Числовая строка int-backed enum по текстовому протоколу приводится к case.
     */
    public function testIntEnumFromNumericStringIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum', ['status' => 'paid', 'priority' => '2']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(Priority::Medium->value, $data['priority']);
    }

    /**
     * По JSON int-backed enum приходит нативным int и приводится к case.
     */
    public function testIntEnumFromNativeIntViaJsonIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/caster/enum',
            server: $this->jsonServerVars(),
            content: json_encode(['status' => 'paid', 'priority' => 3], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(Priority::High->value, $data['priority']);
    }

    /**
     * Значение int enum вне набора вызывает нарушение.
     */
    public function testIntEnumOutOfRangeReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum', ['status' => 'paid', 'priority' => '99']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('priority', $paths);
    }

    // =========================================================================
    // Nullable enum: null и пустая строка
    // =========================================================================

    /**
     * Пустая строка nullable enum по текстовому протоколу становится null.
     */
    public function testNullableEnumEmptyStringBecomesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum', ['status' => 'paid', 'priority' => '']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['priority']);
    }

    /**
     * Отсутствие nullable enum в запросе даёт null.
     */
    public function testNullableEnumAbsentBecomesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/enum', ['status' => 'paid']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['priority']);
    }

    /**
     * Явный null nullable enum по JSON становится null.
     */
    public function testNullableEnumExplicitNullViaJsonBecomesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/caster/enum',
            server: $this->jsonServerVars(),
            content: json_encode(['status' => 'paid', 'priority' => null], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['priority']);
    }
}
