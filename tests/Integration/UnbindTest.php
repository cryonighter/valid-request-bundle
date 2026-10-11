<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

class UnbindTest extends AbstractWebTestCase
{
    // =========================================================================
    // ComputedFieldRequest: безусловный #[UnbindRequestParam] на свойстве slug
    // =========================================================================

    /**
     * Свойство slug отвязано от запроса: поля slug в запросе нет.
     * Валидация проходит успешно, slug вычисляется в конструкторе из title.
     */
    public function testUnbindFieldMissingInRequestSucceeds(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/unbind/computed',
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
     * Свойство slug отвязано от запроса, но передано в теле запроса (payload).
     * Отвязанное свойство исключено из контракта — присутствие поля трактуется
     * как лишнее поле, возникает ошибка валидации.
     */
    public function testUnbindFieldPresentInRequestReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/unbind/computed',
            [
                'title' => 'Hello World',
                'slug' => 'custom-slug',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('slug', $paths);
    }

    // =========================================================================
    // GroupedUnbindRequest: #[UnbindRequestParam(groups: ['Internal'])]
    // =========================================================================

    /**
     * Группа Internal (совпадает с группой атрибута): reference отвязан.
     * Поля reference в запросе нет — валидация успешна,
     * значение берётся из значения по умолчанию.
     */
    public function testInternalGroupUnbindsReferenceWhenMissing(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/unbind/grouped/internal',
            [
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('John', $data['name']);
        $this->assertSame('computed-ref', $data['reference']);
    }

    /**
     * Группа Internal (совпадает с группой атрибута): reference отвязан,
     * но передан в теле запроса (payload) — трактуется как лишнее поле,
     * возникает ошибка валидации.
     */
    public function testInternalGroupReferencePresentReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/unbind/grouped/internal',
            [
                'name' => 'John',
                'reference' => 'user-ref',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('reference', $paths);
    }

    /**
     * Группа Public (не совпадает с группой атрибута): #[UnbindRequestParam] не активен.
     * reference связывается как обычное поле запроса — переданное значение принимается.
     */
    public function testPublicGroupBindsReferenceWhenPresent(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/unbind/grouped/public',
            [
                'name' => 'John',
                'reference' => 'user-ref',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('John', $data['name']);
        $this->assertSame('user-ref', $data['reference']);
    }

    /**
     * Группа Public (не совпадает с группой атрибута): #[UnbindRequestParam] не активен,
     * reference ведёт себя как обычное поле. Поля reference в запросе нет,
     * но у свойства есть значение по умолчанию — поле необязательно, валидация успешна.
     */
    public function testPublicGroupReferenceMissingUsesDefault(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/unbind/grouped/public',
            [
                'name' => 'John',
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('John', $data['name']);
        $this->assertSame('computed-ref', $data['reference']);
    }
}
