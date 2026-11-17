<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use UnexpectedValueException;

class ExpressionTest extends AbstractWebTestCase
{
    /**
     * Object: без mode выбирается Default — email обязателен, phone не проверяется.
     */
    public function testObjectExpressionSelectsDefaultGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/object', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Object: группа Default — отсутствие email даёт violation, phone не требуется.
     */
    public function testObjectExpressionDefaultGroupRequiresEmail(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/object', ['name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('email', $paths);
        $this->assertNotContains('phone', $paths);
    }

    /**
     * Object: mode=extra выбирает группу Extra — phone обязателен, email не проверяется.
     */
    public function testObjectExpressionSelectsExtraGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/object?mode=extra', ['phone' => '+123', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Object: группа Extra — отсутствие phone даёт violation, email не требуется.
     */
    public function testObjectExpressionExtraGroupRequiresPhone(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/object?mode=extra', ['name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('phone', $paths);
        $this->assertNotContains('email', $paths);
    }

    /**
     * Prefixed: без mode выбирается Default — email обязателен, phone не проверяется.
     */
    public function testPrefixedExpressionSelectsDefaultGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Prefixed: группа Default — отсутствие email даёт violation, phone не требуется.
     */
    public function testPrefixedExpressionDefaultGroupRequiresEmail(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed', ['name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('email', $paths);
        $this->assertNotContains('phone', $paths);
    }

    /**
     * Prefixed: mode=extra выбирает группу Extra — phone обязателен, email не проверяется.
     */
    public function testPrefixedExpressionSelectsExtraGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed?mode=extra', ['phone' => '+123', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Prefixed: группа Extra — отсутствие phone даёт violation, email не требуется.
     */
    public function testPrefixedExpressionExtraGroupRequiresPhone(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed?mode=extra', ['name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('phone', $paths);
        $this->assertNotContains('email', $paths);
    }

    /**
     * PrefixedString: без mode выбирается Default — email обязателен, phone не проверяется.
     */
    public function testPrefixedStringExpressionSelectsDefaultGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed-string', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * PrefixedString: группа Default — отсутствие email даёт violation, phone не требуется.
     */
    public function testPrefixedStringExpressionDefaultGroupRequiresEmail(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed-string', ['name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('email', $paths);
        $this->assertNotContains('phone', $paths);
    }

    /**
     * PrefixedString: mode=extra выбирает группу Extra — phone обязателен, email не проверяется.
     */
    public function testPrefixedStringExpressionSelectsExtraGroup(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed-string?mode=extra', ['phone' => '+123', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * PrefixedString: группа Extra — отсутствие phone даёт violation, email не требуется.
     */
    public function testPrefixedStringExpressionExtraGroupRequiresPhone(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/prefixed-string?mode=extra', ['name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('phone', $paths);
        $this->assertNotContains('email', $paths);
    }

    /**
     * Обычная группа + выражение: без extra действует только Default.
     */
    public function testMixedPlainGroupAndExpressionWithoutQuery(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/mixed', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Обычная группа + выражение: с extra действуют Default и Extra — нужны и email, и phone.
     */
    public function testMixedPlainGroupAndExpressionWithQuery(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/mixed?extra=1', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('phone', $paths);
        $this->assertNotContains('email', $paths);
    }

    /**
     * Выражение вернуло массив групп: с extra действуют Default и Extra.
     */
    public function testArrayResultExpandsToGroups(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/array-result?extra=1', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('phone', $paths);
    }

    /**
     * Выражение вернуло массив групп: без extra phone не проверяется.
     */
    public function testArrayResultWithoutExtraUsesDefaultOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/array-result', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Выражение внутри GroupSequence вычисляется: mode=extra добавляет проверку phone.
     */
    public function testExpressionInsideGroupSequence(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/in-sequence?mode=extra', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseStatusCodeSame(422);

        $paths = array_column($this->getJsonResponse($browser)['errors'], 'property');

        $this->assertContains('phone', $paths);
    }

    /**
     * Выражение внутри GroupSequence: без mode проверяется только Default.
     */
    public function testExpressionInsideGroupSequenceDefault(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/in-sequence', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Выражение читает заголовок: X-Scope=extra выбирает группу Extra.
     */
    public function testExpressionReadsHeaders(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/by-header', ['phone' => '+123', 'name' => 'Bob'], [], ['HTTP_X_SCOPE' => 'extra']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Выражение читает заголовок: без X-Scope выбирается Default.
     */
    public function testExpressionWithoutHeaderUsesDefault(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/expression/by-header', ['email' => 'a@b.co', 'name' => 'Bob']);

        $this->assertResponseIsSuccessful();
    }

    /**
     * Выражение вернуло не строку — выбрасывается UnexpectedValueException.
     */
    public function testExpressionWithNonStringResultThrows(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(UnexpectedValueException::class);

        $browser->request('POST', '/expression/invalid-result', ['email' => 'a@b.co', 'name' => 'Bob']);
    }
}
