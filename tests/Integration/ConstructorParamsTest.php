<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Exception\ValidRequestException;

class ConstructorParamsTest extends AbstractWebTestCase
{
    /**
     * Параметр конструктора и свойство совпадают по имени —
     * сопоставляются автоматически, объект собирается.
     */
    public function testMatchByName(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/constructor/match-by-name', ['title' => 'Hello']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Hello', $data['title']);
    }

    /**
     * Имя параметра конструктора отличается от свойства, привязка через
     * BindRequestParam(name: 'title') — объект собирается.
     */
    public function testMatchByBindName(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/constructor/match-by-bind-name', ['title' => 'Hello']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Hello', $data['title']);
    }

    /**
     * Без группы Mirror: каждый параметр берёт своё одноимённое поле —
     * значения свойств различаются.
     */
    public function testBindToOtherPropertyWithoutGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/constructor/bind-to-other/plain',
            ['first' => 'A', 'second' => 'B'],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('A', $data['first']);
        $this->assertSame('B', $data['second']);
    }

    /**
     * Группа Mirror: параметр second привязан к полю first —
     * оба свойства получают значение поля first.
     */
    public function testBindToOtherPropertyWithGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/constructor/bind-to-other/mirror',
            ['first' => 'A', 'second' => 'B'],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('A', $data['first']);
        $this->assertSame('A', $data['second']);
    }

    /**
     * Лишний параметр со значением по умолчанию — объект собирается,
     * параметр ни на что не влияет.
     */
    public function testExtraParamWithDefaultValue(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/constructor/extra-default', ['title' => 'Hello']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Hello', $data['title']);
    }

    /**
     * Лишний nullable-параметр без значения по умолчанию — объект собирается,
     * параметр получает null и ни на что не влияет.
     */
    public function testExtraNullableParam(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/constructor/extra-nullable', ['title' => 'Hello']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Hello', $data['title']);
    }

    /**
     * Лишний обязательный (не nullable, без default) параметр —
     * конструктор вызвать нельзя, ожидается ValidRequestException.
     */
    public function testExtraRequiredParamThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request('POST', '/constructor/extra-required', ['title' => 'Hello']);
    }

    /**
     * BindRequestParam ссылается на несуществующее свойство —
     * ожидается ValidRequestException.
     */
    public function testBindToMissingPropertyThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request('POST', '/constructor/bind-to-missing', ['title' => 'Hello']);
    }

    /**
     * BindRequestParam с source на обычном параметре конструктора —
     * ожидается ValidRequestException.
     */
    public function testSourceOnConstructorParamThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request('POST', '/constructor/source-on-param', ['title' => 'Hello']);
    }
}
