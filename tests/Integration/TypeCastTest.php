<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class TypeCastTest extends AbstractWebTestCase
{
    // =========================================================================
    // bool: допустимые строковые представления true
    // =========================================================================

    /**
     * Строка '1' должна быть приведена к true.
     */
    public function testBoolTrueFromStringOne(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => '1']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertTrue($data['flag']);
    }

    /**
     * Строка 'true' должна быть приведена к true.
     */
    public function testBoolTrueFromStringTrue(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'true']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertTrue($data['flag']);
    }

    /**
     * Строка 'on' должна быть приведена к true.
     */
    public function testBoolTrueFromStringOn(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'on']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertTrue($data['flag']);
    }

    /**
     * Строка 'yes' должна быть приведена к true.
     */
    public function testBoolTrueFromStringYes(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'yes']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertTrue($data['flag']);
    }

    // =========================================================================
    // bool: допустимые строковые представления false
    // =========================================================================

    /**
     * Строка '0' должна быть приведена к false.
     */
    public function testBoolFalseFromStringZero(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => '0']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertFalse($data['flag']);
    }

    /**
     * Строка 'false' должна быть приведена к false.
     */
    public function testBoolFalseFromStringFalse(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'false']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertFalse($data['flag']);
    }

    /**
     * Строка 'off' должна быть приведена к false.
     */
    public function testBoolFalseFromStringOff(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'off']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertFalse($data['flag']);
    }

    /**
     * Строка 'no' должна быть приведена к false.
     */
    public function testBoolFalseFromStringNo(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'no']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertFalse($data['flag']);
    }

    // =========================================================================
    // bool: недопустимые значения — ошибка валидации
    // =========================================================================

    /**
     * Произвольная строка 'maybe' не является допустимым булевым значением.
     * Бандл должен вернуть violation для поля flag.
     */
    public function testBoolInvalidStringMaybeReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'maybe']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('flag', $paths);
    }

    /**
     * Строка 'yes_please' не является допустимым булевым значением.
     * Бандл должен вернуть violation для поля flag.
     */
    public function testBoolInvalidStringYesPleaseReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => 'yes_please']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('flag', $paths);
    }

    /**
     * Число '2' не является допустимым булевым значением.
     * Бандл должен вернуть violation для поля flag.
     */
    public function testBoolInvalidStringTwoReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/bool', ['flag' => '2']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('flag', $paths);
    }

    // =========================================================================
    // int: корректное приведение с удалением разделителей тысячных
    // =========================================================================

    /**
     * Обычное целое число без разделителей.
     */
    public function testIntPlainNumberParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => '1000']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1000, $data['amount']);
    }

    /**
     * Число с пробелом как разделителем тысячных: '1 000' → 1000.
     */
    public function testIntWithSpaceThousandSeparatorParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => '1 000']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1000, $data['amount']);
    }

    /**
     * Число с апострофом как разделителем тысячных: '1\'000' → 1000.
     */
    public function testIntWithApostropheThousandSeparatorParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => "1'000"]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1000, $data['amount']);
    }

    /**
     * Число с точкой как разделителем тысячных: '1.000' → 1000.
     */
    public function testIntWithDotThousandSeparatorParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => '1.000']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1000, $data['amount']);
    }

    /**
     * Число с запятой как разделителем тысячных: '1,000' → 1000.
     */
    public function testIntWithCommaThousandSeparatorParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => '1,000']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1000, $data['amount']);
    }

    /**
     * Большое число с несколькими разделителями тысячных: '1 000 000' → 1000000.
     */
    public function testIntLargeNumberWithMultipleSeparatorsParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => '1 000 000']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(1000000, $data['amount']);
    }

    /**
     * Строка 'two' не является числом — нарушает Positive.
     * Бандл должен вернуть violation для поля amount.
     */
    public function testIntNonNumericStringReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => 'two']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('amount', $paths);
    }

    /**
     * Отрицательное число нарушает Positive.
     * Violation для поля amount, исходное значение сохраняется.
     */
    public function testIntNegativeNumberReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/int', ['amount' => '-5']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('amount', $paths);
    }

    // =========================================================================
    // float: корректное приведение с определением разделителя
    // Тут базово - более
    // =========================================================================

    /**
     * Дробное число с точкой как разделителем целой и дробной части: '19.99' → 19.99.
     */
    public function testFloatWithDotDecimalSeparatorParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/float', ['price' => '19.99']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertEqualsWithDelta(19.99, $data['price'], 0.001);
    }

    /**
     * Дробное число с запятой как разделителем целой и дробной части: '19,99' → 19.99.
     */
    public function testFloatWithCommaDecimalSeparatorParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/float', ['price' => '19,99']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertEqualsWithDelta(19.99, $data['price'], 0.001);
    }

    /**
     * Число с пробелом как разделителем тысячных и точкой как разделителем
     * дробной части: '1 234.56' → 1234.56.
     */
    public function testFloatWithSpaceThousandAndDotDecimalParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/float', ['price' => '1 234.56']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertEqualsWithDelta(1234.56, $data['price'], 0.001);
    }

    /**
     * Целое число без дробной части корректно приводится к float: '42' → 42.0.
     */
    public function testFloatFromIntegerStringParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/float', ['price' => '42']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertEqualsWithDelta(42.0, $data['price'], 0.001);
    }

    /**
     * Строка 'free' не является числом.
     * Бандл должен вернуть violation для поля price.
     */
    public function testFloatNonNumericStringReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/float', ['price' => 'free']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('price', $paths);
    }

    /**
     * Отрицательное дробное число нарушает Positive.
     */
    public function testFloatNegativeNumberReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/typecast/float', ['price' => '-9.99']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('price', $paths);
    }

    // =========================================================================
    // Все три типа вместе
    // =========================================================================

    /**
     * Все три поля переданы в виде form-строк и корректно приведены к нужным типам.
     */
    public function testAllTypesValidFormDataParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/typecast/all',
            [
                'active' => 'yes',
                'count' => '1 500',
                'price' => '99,90',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertTrue($data['active']);
        $this->assertSame(1500, $data['count']);
        $this->assertEqualsWithDelta(99.90, $data['price'], 0.001);
    }

    /**
     * Невалидные значения для всех трёх полей одновременно.
     * Violations для каждого поля.
     */
    public function testAllTypesInvalidValuesReceiveViolationsForAllFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/typecast/all',
            [
                'active' => 'maybe',
                'count' => 'many',
                'price' => 'cheap',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('active', $paths);
        $this->assertContains('count', $paths);
        $this->assertContains('price', $paths);
    }

    /**
     * JSON-формат: значения передаются нативными JSON-типами (не строками).
     * Бандл должен корректно обработать их без преобразования.
     */
    public function testAllTypesInvalidJsonDataParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/typecast/all',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'active' => true,
                    'count' => 1500,
                    'price' => 99.90,
                ],
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertTrue($data['active']);
        $this->assertSame(1500, $data['count']);
        $this->assertEqualsWithDelta(99.90, $data['price'], 0.001);
    }

    /**
     * JSON-формат: значения передаются строками, но для этого типа преобразования нет.
     * Violations для каждого поля.
     */
    public function testAllTypesValidJsonDataParsedCorrectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/typecast/all',
            [],
            [],
            $this->jsonServerVars(),
            json_encode(
                [
                    'active' => 'true',
                    'count' => '1 500',
                    'price' => '99.90',
                ],
            ),
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('active', $paths);
        $this->assertContains('count', $paths);
        $this->assertContains('price', $paths);
    }
}
