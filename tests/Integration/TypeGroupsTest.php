<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Exception\ValidRequestException;

class TypeGroupsTest extends AbstractWebTestCase
{
    // =========================================================================
    // Тип свойства — интерфейс. Класс задан через Assert\Type(CardPayment).
    // =========================================================================

    /**
     * Валидные данные собираются в CardPayment и проходят валидацию.
     */
    public function testInterfaceTypeBuildsConcreteClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/interface',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '4111111111111111',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('CardPayment', $data['class']);
        $this->assertSame('card', $data['label']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('4111111111111111', $data['cardNumber']);
    }

    /**
     * Невалидное внутреннее поле (cardNumber не проходит Luhn) даёт violation.
     */
    public function testInterfaceTypeInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/interface',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '1234567890123456',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('method.cardNumber', $paths);
    }

    // =========================================================================
    // Тип свойства — абстрактный класс. Класс задан через Assert\Type(CryptoPayment).
    // =========================================================================

    /**
     * Валидные данные собираются в CryptoPayment и проходят валидацию.
     */
    public function testAbstractTypeBuildsConcreteClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/abstract',
            [
                'method' => [
                    'currency' => 'EUR',
                    'walletAddress' => '1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('CryptoPayment', $data['class']);
        $this->assertSame('crypto', $data['label']);
        $this->assertSame('EUR', $data['currency']);
        $this->assertSame('1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2', $data['walletAddress']);
    }

    /**
     * Слишком короткий walletAddress даёт violation.
     */
    public function testAbstractTypeInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/abstract',
            [
                'method' => [
                    'currency' => 'EUR',
                    'walletAddress' => 'short',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('method.walletAddress', $paths);
    }

    // =========================================================================
    // Тип свойства — обычный класс, нужен наследник EmailNotification.
    // =========================================================================

    /**
     * Валидные данные собираются в наследника EmailNotification.
     */
    public function testSubclassTypeBuildsSubclass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/subclass',
            [
                'notification' => [
                    'subject' => 'Welcome',
                    'to' => 'user@example.com',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('EmailNotification', $data['class']);
        $this->assertSame('Welcome', $data['subject']);
        $this->assertSame('user@example.com', $data['to']);
    }

    /**
     * Невалидный email в наследнике даёт violation.
     */
    public function testSubclassTypeInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/subclass',
            [
                'notification' => [
                    'subject' => 'Welcome',
                    'to' => 'not-an-email',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('notification.to', $paths);
    }

    // =========================================================================
    // Тип свойства — object. Класс задан через Assert\Type(CardPayment).
    // =========================================================================

    /**
     * Валидные данные собираются в CardPayment.
     */
    public function testObjectTypeBuildsConcreteClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/object',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '4111111111111111',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('CardPayment', $data['class']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('4111111111111111', $data['cardNumber']);
    }

    /**
     * Невалидный cardNumber даёт violation.
     */
    public function testObjectTypeInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/object',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '1234567890123456',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('method.cardNumber', $paths);
    }

    // =========================================================================
    // Тип свойства — mixed. Класс задан через Assert\Type(CardPayment).
    // =========================================================================

    /**
     * Валидные данные собираются в CardPayment.
     */
    public function testMixedTypeBuildsConcreteClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/mixed',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '4111111111111111',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('CardPayment', $data['class']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('4111111111111111', $data['cardNumber']);
    }

    /**
     * Невалидный cardNumber даёт violation.
     */
    public function testMixedTypeInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/mixed',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '1234567890123456',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('method.cardNumber', $paths);
    }

    // =========================================================================
    // Свойство без типа. Класс задан через Assert\Type(CardPayment).
    // =========================================================================

    /**
     * Валидные данные собираются в CardPayment.
     */
    public function testUntypedBuildsConcreteClass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/untyped',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '4111111111111111',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('CardPayment', $data['class']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('4111111111111111', $data['cardNumber']);
    }

    /**
     * Невалидный cardNumber даёт violation.
     */
    public function testUntypedInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/untyped',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '1234567890123456',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('method.cardNumber', $paths);
    }

    // =========================================================================
    // Два Assert\Type с группами: обычный класс с подклассами.
    // =========================================================================

    /**
     * Группа Email: активен Assert\Type(EmailNotification) — собирается EmailNotification.
     */
    public function testSubclassGroupsEmailBuildsEmailNotification(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/subclass/email',
            [
                'notification' => [
                    'subject' => 'Welcome',
                    'to' => 'user@example.com',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('EmailNotification', $data['class']);
        $this->assertSame('Welcome', $data['subject']);
        $this->assertSame('user@example.com', $data['to']);
    }

    /**
     * Группа Sms: активен Assert\Type(SmsNotification) — собирается SmsNotification.
     */
    public function testSubclassGroupsSmsBuildsSmsNotification(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/subclass/sms',
            [
                'notification' => [
                    'subject' => 'Code',
                    'phone' => '+79001234567',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('SmsNotification', $data['class']);
        $this->assertSame('Code', $data['subject']);
        $this->assertSame('+79001234567', $data['phone']);
    }

    /**
     * Группа Sms: невалидный phone даёт violation.
     */
    public function testSubclassGroupsSmsInvalidFieldReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/subclass/sms',
            [
                'notification' => [
                    'subject' => 'Code',
                    'phone' => 'not-a-phone',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('notification.phone', $paths);
    }

    /**
     * Группа Sms: вместо объекта SmsNotification передается валидный EmailNotification, это даёт violation.
     */
    public function testSubclassGroupsSmsInvalidEmailNotificationViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/subclass/sms',
            [
                'notification' => [
                    'subject' => 'Welcome',
                    'to' => 'user@example.com',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('notification.phone', $paths); // Должно быть, но его нет
        $this->assertContains('notification.to', $paths); // Не должно быть, но оно есть
    }

    // =========================================================================
    // Два Assert\Type с группами на mixed: объект или примитивный id.
    // =========================================================================

    /**
     * Группа Full: активен Assert\Type(CardPayment) — method собирается в объект.
     */
    public function testObjectOrIdFullBuildsObject(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/object-or-id/full',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '4111111111111111',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('CardPayment', $data['method']['class']);
        $this->assertSame('4111111111111111', $data['method']['cardNumber']);
    }

    /**
     * Группа Ref: активен Assert\Type('digit') — method остаётся примитивным string.
     */
    public function testObjectOrIdRefBuildsPrimitiveId(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/type-groups/object-or-id/ref', ['method' => 42]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        // Для mixed не доступно приведение типа (если нет кастомного кастера, а в этом тесте его нет).
        // Поэтому мы не можем использовать int в при передаче формы.
        // Используем digit (строка, содержащая только цифры).
        $this->assertSame('string', $data['type']);
        $this->assertSame('42', $data['method']);
    }

    /**
     * Группа Ref: нецелое значение не проходит Assert\Type('digit') — violation.
     */
    public function testObjectOrIdRefInvalidPrimitiveReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/type-groups/object-or-id/ref', ['method' => 'not-an-digit']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('method', $paths);
    }

    // =========================================================================
    // Кейс A: конкретный класс в тайпхинте + один Assert\Type(наследник) с группой.
    // =========================================================================

    /**
     * Группа Email активна: класс берётся из Assert\Type — собирается наследник EmailNotification.
     */
    public function testOptionalSubclassWithGroupBuildsSubclass(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/optional-subclass/with-group',
            [
                'notification' => [
                    'subject' => 'Welcome',
                    'to' => 'user@example.com',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('EmailNotification', $data['class']);
        $this->assertSame('Welcome', $data['subject']);
        $this->assertSame('user@example.com', $data['to']);
    }

    /**
     * Группа Email не активна: Assert\Type не срабатывает — класс берётся из тайпхинта Notification.
     */
    public function testOptionalSubclassWithoutGroupUsesTypeHint(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/type-groups/optional-subclass/without-group',
            [
                'notification' => [
                    'subject' => 'Welcome',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('Notification', $data['class']);
        $this->assertSame('Welcome', $data['subject']);
    }

    // =========================================================================
    // Кейс B: тайпхинт — абстрактный класс, Assert\Type только для чужой группы.
    // Активна только Default — ни один Assert\Type не подходит, абстракцию не создать.
    // =========================================================================

    /**
     * Ожидается ValidRequestException: инстанцировать абстрактный класс из тайпхинта нельзя,
     * а подходящего Assert\Type для активной группы Default нет.
     */
    public function testUnresolvableAbstractThrowsException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request(
            'POST',
            '/type-groups/unresolvable-abstract',
            [
                'method' => [
                    'currency' => 'USD',
                    'cardNumber' => '4111111111111111',
                ],
            ],
        );
    }

    // =========================================================================
    // Кейс C: тайпхинт — интерфейс, Assert\Type — абстрактный класс (нельзя инстанцировать).
    // =========================================================================

    /**
     * Ожидается ValidRequestException: Assert\Type указывает на абстрактный класс.
     */
    public function testAbstractInTypeThrowsException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request(
            'POST',
            '/type-groups/abstract-in-type',
            [
                'method' => [
                    'currency' => 'USD',
                ],
            ],
        );
    }

    // =========================================================================
    // Кейс D: тайпхинт — интерфейс, Assert\Type — примитив (нельзя переопределить объект).
    // =========================================================================

    /**
     * Ожидается ValidRequestException: примитивным типом объектный тайпхинт переопределить нельзя.
     */
    public function testPrimitiveInTypeThrowsException(): void
    {
        $browser = $this->createThrowingBrowser();

        $this->expectException(ValidRequestException::class);

        $browser->request('POST', '/type-groups/primitive-in-type', ['method' => 42]);
    }
}
