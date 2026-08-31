<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class DateTimeCasterTest extends AbstractWebTestCase
{
    // =========================================================================
    // Строковые даты
    // =========================================================================

    /**
     * Валидная строковая дата приводится к DateTimeImmutable.
     */
    public function testDateStringIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '2025-06-15']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('2025-06-15 00:00:00.000000', $data['createdAt']);
    }

    /**
     * Невалидная строка даты не приводится и вызывает нарушение.
     */
    public function testInvalidDateStringReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => 'not-a-date']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('createdAt', $paths);
    }

    // =========================================================================
    // Timestamp: положительный, ноль, отрицательный, дробный
    // =========================================================================

    /**
     * Положительный числовой timestamp приводится через createFromTimestamp.
     */
    public function testPositiveTimestampIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '1750000000']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('2025-06-15 15:06:40.000000', $data['createdAt']);
    }

    /**
     * Ноль допустим как timestamp — эпоха Unix.
     */
    public function testZeroTimestampIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '0']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('1970-01-01 00:00:00.000000', $data['createdAt']);
    }

    /**
     * Отрицательный timestamp допустим — дата до эпохи Unix.
     */
    public function testNegativeTimestampIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '-86400']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('1969-12-31 00:00:00.000000', $data['createdAt']);
    }

    /**
     * Дробный timestamp: дробная часть интерпретируется как микросекунды.
     */
    public function testFractionalTimestampIsCastWithMicroseconds(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '1750000000.5']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('2025-06-15 15:06:40.500000', $data['createdAt']);
    }

    /**
     * Числовая строка без разделителей трактуется как timestamp, а не как дата.
     * "20250615" => секунды эпохи Unix, а не 15 июня 2025.
     */
    public function testNumericStringIsTreatedAsTimestamp(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '20250615']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('1970-08-23 09:10:15.000000', $data['createdAt']);
    }

    // =========================================================================
    // Nullable DateTime: null и пустая строка
    // =========================================================================

    /**
     * Пустая строка nullable-даты по текстовому протоколу становится null.
     */
    public function testNullableEmptyStringBecomesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '2025-06-15', 'updatedAt' => '']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['updatedAt']);
    }

    /**
     * Отсутствие nullable-даты в запросе даёт null.
     */
    public function testNullableAbsentBecomesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '2025-06-15']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['updatedAt']);
    }

    /**
     * Явный null nullable-даты по JSON-протоколу становится null.
     */
    public function testNullableExplicitNullViaJsonBecomesNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/caster/datetime',
            server: $this->jsonServerVars(),
            content: json_encode(['createdAt' => '2025-06-15', 'updatedAt' => null], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNull($data['updatedAt']);
    }

    /**
     * Валидная nullable-дата приводится к DateTime.
     */
    public function testNullableValidDateIsCast(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime', ['createdAt' => '2025-06-15', 'updatedAt' => '2025-07-01']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('2025-07-01 00:00:00.000000', $data['updatedAt']);
    }

    // =========================================================================
    // Not null DateTimeImmutable: отсутствие значения
    // =========================================================================

    /**
     * Отсутствие обязательной даты вызывает нарушение NotNull.
     */
    public function testMissingRequiredDateReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/caster/datetime');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('createdAt', $paths);
    }
}
