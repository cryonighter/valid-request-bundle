<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class HydrationTest extends AbstractWebTestCase
{
    // =========================================================================
    // Сборка через конструктор
    // =========================================================================

    /**
     * Имя параметра запроса ('login') отличается от имени свойства ($username)
     * и параметра конструктора ($email). Значение корректно доходит до объекта
     * благодаря #[BindRequestParam(name: 'login')] в обоих местах.
     */
    public function testConstructorMapsRenamedParam(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/constructor',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }

    // =========================================================================
    // Сборка через сеттер
    // =========================================================================

    /**
     * Свойства нет в конструкторе, но есть публичный сеттер setUsername():
     * бандл гидрирует объект через него.
     */
    public function testSetterHydratesProperty(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/setter',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }

    // =========================================================================
    // Сборка через публичное свойство
    // =========================================================================

    /**
     * Нет конструктора и сеттера, но свойство публичное:
     * бандл устанавливает значение напрямую в публичное свойство.
     */
    public function testPublicPropertyHydratesDirectly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/public-property',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }

    // =========================================================================
    // Сборка через рефлексию
    // =========================================================================

    /**
     * Приватное свойство без конструктора и сеттера:
     * единственный способ гидрации — прямая установка через рефлексию.
     */
    public function testReflectionHydratesPrivateProperty(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/reflection',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }

    // =========================================================================
    // Комбинированная сборка
    // =========================================================================

    /**
     * В одном классе четыре свойства, каждое гидрируется своей стратегией
     * (конструктор, сеттер, публичное свойство, рефлексия).
     * Имена полей запроса совпадают с именами свойств, маппинг автоматический.
     */
    public function testCombinedHydratesEachPropertyWithItsOwnStrategy(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/combined',
            [
                'viaConstructor' => 'constructor-value',
                'viaSetter' => 'setter-value',
                'viaPublic' => 'public-value',
                'viaReflection' => 'reflection-value',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('constructor-value', $data['viaConstructor']);
        $this->assertSame('setter-value', $data['viaSetter']);
        $this->assertSame('public-value', $data['viaPublic']);
        $this->assertSame('reflection-value', $data['viaReflection']);

        // Проверяем, что значения по умолчанию корректно установлены в конструкторе и в свойстве класса
        $this->assertSame('constructor-default-value', $data['viaConstructorDefault']);
        $this->assertSame('default-value', $data['viaDefault']);
    }

    // =========================================================================
    // Отклонение неподходящих стратегий (fallback в рефлексию)
    // =========================================================================

    /**
     * Сеттер setUsername() существует, но он приватный.
     * Контракт требует публичный сеттер, поэтому используется рефлексия.
     */
    public function testPrivateSetterFallsBackToReflection(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/private-setter',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }

    /**
     * Сеттер setUsername() публичный, но принимает два аргумента.
     * Контракт требует ровно один, поэтому используется рефлексия,
     * и значение не проходит через логику конкатенации в сеттере.
     */
    public function testTwoArgsSetterFallsBackToReflection(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/two-args-setter',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }

    /**
     * Свойство публичное, но readonly и не инициализируется конструктором.
     * Прямое присваивание невозможно, поэтому используется рефлексия.
     */
    public function testPublicReadonlyFallsBackToReflection(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/builder/public-readonly',
            [
                'login' => 'user@example.com',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('user@example.com', $data['username']);
    }
}
