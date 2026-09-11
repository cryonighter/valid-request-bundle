<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Exception\ValidRequestException;

class ObjectTypeTest extends AbstractWebTestCase
{
    // =========================================================================
    // Без Assert\Type: класс определяется типом свойства
    // =========================================================================

    /**
     * Тип свойства object → инстанцируется stdClass с полями из запроса.
     */
    public function testObjectPropertyBecomesStdClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/plain',
            ['asObject' => ['foo' => 'bar', 'baz' => 'qux'], 'asStdClass' => []],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('stdClass', $data['asObject']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['asObject']['fields']);
    }

    /**
     * Тип свойства stdClass → инстанцируется stdClass с полями из запроса.
     */
    public function testStdClassPropertyStaysStdClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/plain',
            ['asObject' => [], 'asStdClass' => ['foo' => 'bar', 'baz' => 'qux']],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('stdClass', $data['asStdClass']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['asStdClass']['fields']);
    }

    // =========================================================================
    // С Assert\Type: тип свойства перекрывается атрибутом, итог всегда stdClass
    // =========================================================================

    /**
     * stdClass + Assert\Type('stdClass') → stdClass.
     */
    public function testStdClassTypedStdClass(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('stdClass', $data['stdClassTypedStd']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['stdClassTypedStd']['fields']);
    }

    /**
     * stdClass + Assert\Type('object') → stdClass.
     */
    public function testStdClassTypedObject(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('stdClass', $data['stdClassTypedObject']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['stdClassTypedObject']['fields']);
    }

    /**
     * object + Assert\Type('object') → stdClass.
     */
    public function testObjectTypedObject(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('stdClass', $data['objectTypedObject']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['objectTypedObject']['fields']);
    }

    /**
     * mixed + Assert\Type('object') → stdClass.
     */
    public function testMixedTypedObject(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('stdClass', $data['mixedTypedObject']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['mixedTypedObject']['fields']);
    }

    /**
     * mixed + Assert\Type('stdClass') → stdClass.
     */
    public function testMixedTypedStdClass(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('stdClass', $data['mixedTypedStd']['class']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['mixedTypedStd']['fields']);
    }

    /**
     * Объектный тип свойства + примитивный Assert\Type, данные соответствуют
     * примитиву: валидация проходит, но собрать объект нельзя → ValidRequestException.
     */
    public function testObjectPropertyWithPrimitiveTypeAndPrimitiveDataThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request(
            'POST',
            '/object-type/conflict',
            server: $this->jsonServerVars(),
            content: json_encode(['value' => 42], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Объектный тип свойства + примитивный Assert\Type, данные соответствуют
     * объекту: примитивный Assert\Type не проходит → ошибка валидации.
     */
    public function testObjectPropertyWithPrimitiveTypeAndObjectDataFailsValidation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/conflict',
            server: $this->jsonServerVars(),
            content: json_encode(['value' => ['foo' => 'bar']], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('value', $paths);
    }

    /**
     * Вложенность: ассоциативный вложенный объект приводится к stdClass,
     * индексный массив остаётся индексным массивом.
     */
    public function testNestedAssocBecomesStdClassAndListStaysArray(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/nested',
            server: $this->jsonServerVars(),
            content: json_encode(
                [
                    'data' => [
                        'nested' => ['foo' => 'bar'],
                        'list' => ['a', 'b', 'c'],
                    ],
                ],
                JSON_THROW_ON_ERROR,
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $root = $data['data'];

        // Корень — stdClass.
        $this->assertSame('stdClass', $root['type']);

        // Ассоциативное вложенное поле стало stdClass.
        $this->assertSame('stdClass', $root['fields']['nested']['type']);
        $this->assertSame('bar', $root['fields']['nested']['fields']['foo']['value']);

        // Индексное поле осталось индексным массивом.
        $this->assertSame('array', $root['fields']['list']['type']);
        $this->assertTrue($root['fields']['list']['list']);
    }

    /**
     * Nullable object-свойства с явным null в запросе → свойства получают null.
     */
    public function testNullableObjectWithExplicitNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/nullable',
            server: $this->jsonServerVars(),
            content: json_encode(
                ['asObject' => null, 'asStdClass' => null],
                JSON_THROW_ON_ERROR,
            ),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('null', $data['asObject']);
        $this->assertSame('null', $data['asStdClass']);
    }

    /**
     * Nullable object-свойства при полном отсутствии полей → свойства получают
     * null (за счёт значения по умолчанию).
     */
    public function testNullableObjectWithMissingFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/nullable',
            server: $this->jsonServerVars(),
            content: json_encode([], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('null', $data['asObject']);
        $this->assertSame('null', $data['asStdClass']);
    }

    /**
     * Отправляет одинаковую нагрузку во все свойства TypedObjectRequest.
     *
     * @return array<string, array{class: string, fields: array<string, mixed>}>
     */
    private function requestTyped(): array
    {
        $payload = ['foo' => 'bar', 'baz' => 'qux'];

        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/object-type/typed',
            [
                'stdClassTypedStd' => $payload,
                'stdClassTypedObject' => $payload,
                'objectTypedObject' => $payload,
                'mixedTypedObject' => $payload,
                'mixedTypedStd' => $payload,
            ],
        );

        $this->assertResponseIsSuccessful();

        return $this->getJsonResponse($browser);
    }
}
