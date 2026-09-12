<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Exception\ValidRequestException;

class IterableTypeTest extends AbstractWebTestCase
{
    /**
     * Тип свойства iterable → приводится к обычному индексному array.
     */
    public function testIterablePropertyBecomesArray(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/iterable-type/plain',
            ['value' => ['a', 'b', 'c']],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('array', $data['value']['type']);
        $this->assertTrue($data['value']['list']);
        $this->assertSame(['a', 'b', 'c'], $data['value']['items']);
    }

    /**
     * iterable + Assert\Type('array') → array.
     */
    public function testIterableTypedArray(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('array', $data['iterableTypedArray']['type']);
        $this->assertTrue($data['iterableTypedArray']['list']);
        $this->assertSame(['a', 'b', 'c'], $data['iterableTypedArray']['items']);
    }

    /**
     * iterable + Assert\Type('iterable') → array.
     */
    public function testIterableTypedIterable(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('array', $data['iterableTypedIterable']['type']);
        $this->assertTrue($data['iterableTypedIterable']['list']);
        $this->assertSame(['a', 'b', 'c'], $data['iterableTypedIterable']['items']);
    }

    /**
     * array + Assert\Type('array') → array.
     */
    public function testArrayTypedArray(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('array', $data['arrayTypedArray']['type']);
        $this->assertTrue($data['arrayTypedArray']['list']);
        $this->assertSame(['a', 'b', 'c'], $data['arrayTypedArray']['items']);
    }

    /**
     * mixed + Assert\Type('array') → array.
     */
    public function testMixedTypedArray(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('array', $data['mixedTypedArray']['type']);
        $this->assertTrue($data['mixedTypedArray']['list']);
        $this->assertSame(['a', 'b', 'c'], $data['mixedTypedArray']['items']);
    }

    /**
     * mixed + Assert\Type('iterable') → array.
     */
    public function testMixedTypedIterable(): void
    {
        $data = $this->requestTyped();

        $this->assertSame('array', $data['mixedTypedIterable']['type']);
        $this->assertTrue($data['mixedTypedIterable']['list']);
        $this->assertSame(['a', 'b', 'c'], $data['mixedTypedIterable']['items']);
    }

    /**
     * Отправляет одинаковую индексную нагрузку во все свойства TypedIterableRequest.
     *
     * @return array<string, array{type: string, list: bool, items: mixed}>
     */
    private function requestTyped(): array
    {
        $payload = ['a', 'b', 'c'];

        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/iterable-type/typed',
            [
                'iterableTypedArray' => $payload,
                'iterableTypedIterable' => $payload,
                'arrayTypedArray' => $payload,
                'mixedTypedArray' => $payload,
                'mixedTypedIterable' => $payload,
            ],
        );

        $this->assertResponseIsSuccessful();

        return $this->getJsonResponse($browser);
    }

    /**
     * Ассоциативный массив в iterable-свойстве остаётся ассоциативным массивом
     * (а не превращается в stdClass, как это было бы для object-типа).
     */
    public function testAssocArrayStaysAssocArray(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/iterable-type/plain',
            ['value' => ['foo' => 'bar', 'baz' => 'qux']],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('array', $data['value']['type']);
        $this->assertFalse($data['value']['list']);
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $data['value']['items']);
    }

    /**
     * Nullable iterable-свойство с явным null в запросе → свойство получает null.
     */
    public function testNullableIterableWithExplicitNull(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/iterable-type/nullable',
            server: $this->jsonServerVars(),
            content: json_encode(['value' => null], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('null', $data['type']);
    }

    /**
     * Nullable iterable-свойство при отсутствии поля → свойство получает null
     * (за счёт значения по умолчанию).
     */
    public function testNullableIterableWithMissingField(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/iterable-type/nullable',
            server: $this->jsonServerVars(),
            content: json_encode([], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('null', $data['type']);
    }

    /**
     * iterable + примитивный Assert\Type, данные — коллекция:
     * примитивный Assert\Type('int') не проходит → ошибка валидации.
     */
    public function testIterableWithPrimitiveTypeAndArrayDataFailsValidation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/iterable-type/conflict-primitive',
            server: $this->jsonServerVars(),
            content: json_encode(['value' => ['a', 'b']], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('value', $paths);
    }

    /**
     * iterable + примитивный Assert\Type, данные — примитив:
     * валидация Assert\Type('int') проходит, но тип свойства iterable требует
     * коллекцию → структурная невозможность собрать значение, ожидается ValidRequestException.
     */
    public function testIterableWithPrimitiveTypeAndPrimitiveDataThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request(
            'POST',
            '/iterable-type/conflict-primitive',
            server: $this->jsonServerVars(),
            content: json_encode(['value' => 42], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * iterable + объектный Assert\Type: тип свойства — коллекция, а Assert\Type указывает на объект.
     * Ожидается ошибка валидации - в ветку объектов он не зайдет и преобразования в объект не случится,
     * а значит при вервичной валидации отвалится по Assert\Type, ведь массив сылых данных - не объект.
     */
    public function testIterableWithObjectTypeThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $browser->request(
            'POST',
            '/iterable-type/conflict-object',
            server: $this->jsonServerVars(),
            content: json_encode(['value' => ['foo' => 'bar']], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('value', $paths);
    }
}
