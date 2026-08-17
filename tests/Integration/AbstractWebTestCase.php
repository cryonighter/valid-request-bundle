<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Базовый класс для всех интеграционных тестов бандла.
 */
abstract class AbstractWebTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Восстанавливаем обработчики исключений, зарегистрированные Symfony
        // в режиме debug, чтобы PHPUnit не считал тест risky.
        while (true) {
            $previous = set_exception_handler(null);

            restore_exception_handler();

            if ($previous === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    /**
     * Создаёт браузер для теста.
     *
     * @return KernelBrowser
     */
    protected function createBrowser(): KernelBrowser
    {
        return static::createClient();
    }

    /**
     * Создаёт браузер с отключённым перехватом исключений.
     * Используется в тестах, где ожидается выброс исключения.
     *
     * @return KernelBrowser
     */
    protected function createThrowingBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $browser->catchExceptions(false);

        return $browser;
    }

    /**
     * Декодирует JSON из последнего ответа браузера.
     */
    protected function getJsonResponse(KernelBrowser $browser): mixed
    {
        return json_decode($browser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Формирует server-массив для JSON-запроса.
     */
    protected function jsonServerVars(): array
    {
        return ['CONTENT_TYPE' => 'application/json'];
    }
}
