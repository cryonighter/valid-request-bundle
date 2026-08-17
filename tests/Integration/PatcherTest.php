<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\TimestampPatcher;

class PatcherTest extends AbstractWebTestCase
{
    // =========================================================================
    // Патчер на уровне свойства (BindRequestParam patcher)
    // =========================================================================

    /**
     * slug передан явно: патчер не должен его перезаписывать,
     * возвращается переданное значение.
     */
    public function testPropertyPatcherDoesNotOverrideExplicitSlug(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/article',
            [
                'title' => 'Hello World',
                'slug' => 'my-custom-slug',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Hello World', $data['title']);
        $this->assertSame('my-custom-slug', $data['slug']);
    }

    /**
     * slug не передан: патчер генерирует его из поля title.
     * 'Hello World' → 'hello-world'.
     */
    public function testPropertyPatcherGeneratesSlugFromTitle(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/article',
            [
                'title' => 'Hello World',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Hello World', $data['title']);
        $this->assertSame('hello-world', $data['slug']);
    }

    /**
     * slug передан пустой строкой: патчер считает значение
     * отсутствующим и генерирует slug из title.
     */
    public function testPropertyPatcherTreatsEmptySlugAsMissing(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/article',
            [
                'title' => 'Hello World',
                'slug' => '',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertNotEmpty($data['slug']);
        $this->assertSame('hello-world', $data['slug']);
    }

    /**
     * Явно переданный slug не соответствует паттерну Regex:
     * патчер не трогает значение, валидация выявляет нарушение.
     */
    public function testPropertyPatcherExplicitInvalidSlugReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/article',
            [
                'title' => 'Hello World',
                'slug' => 'Invalid Slug!!!',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('slug', $paths);
    }

    /**
     * title не передан: slug не может быть сгенерирован
     * патчером из пустого title, violations для обоих полей.
     */
    public function testPropertyPatcherMissingTitleReceivesViolationsForBothFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/patcher/article', []);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('title', $paths);
        $this->assertContains('slug', $paths);
    }

    // =========================================================================
    // Патчер на уровне объекта (ValidRequest patcher)
    // =========================================================================

    /**
     * createdAt передан явно: патчер не должен его перезаписывать.
     */
    public function testObjectPatcherDoesNotOverrideExplicitCreatedAt(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/object',
            [
                'name' => 'Test Article',
                'createdAt' => '2025-06-15',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Test Article', $data['name']);
        $this->assertSame('2025-06-15', $data['created_at']);
    }

    /**
     * createdAt не передан: патчер подставляет дефолтное значение,
     * объект успешно собирается без violation.
     */
    public function testObjectPatcherFillsMissingCreatedAt(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/object',
            [
                'name' => 'Test Article',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Test Article', $data['name']);
        $this->assertSame(TimestampPatcher::DEFAULT_TIMESTAMP, $data['created_at']);
    }

    /**
     * createdAt передан пустой строкой: патчер считает значение
     * отсутствующим и подставляет дефолтное.
     */
    public function testObjectPatcherTreatsEmptyCreatedAtAsMissing(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/patcher/object',
            [
                'name' => 'Test Article',
                'createdAt' => '',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame(TimestampPatcher::DEFAULT_TIMESTAMP, $data['created_at']);
    }

    /**
     * name не передан, createdAt подставляется патчером:
     * violation только для поля name, createdAt успешно заполнен.
     */
    public function testObjectPatcherFillsCreatedAtButMissingNameReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/patcher/object');

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('name', $paths);
        $this->assertNotContains('createdAt', $paths);
    }
}
