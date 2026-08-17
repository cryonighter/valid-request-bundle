<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration;

use Cryonighter\ValidRequestBundle\Tests\Integration\Concern\CreatesUploadedFiles;

class FileTest extends AbstractWebTestCase
{
    use CreatesUploadedFiles;

    // =========================================================================
    // Обязательный файл (NotNull)
    // =========================================================================

    /**
     * Валидный JPEG-файл и корректный title: объект успешно собирается,
     * возвращаются имя и MIME-тип файла.
     */
    public function testValidJpegFileAndTitleReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload',
            ['title' => 'My Avatar'],
            ['avatar' => $this->createJpegFile()],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('My Avatar', $data['title']);
        $this->assertSame('avatar.jpg', $data['avatar_name']);
        $this->assertSame('image/jpeg', $data['avatar_mime']);
    }

    /**
     * Валидный PNG-файл: MIME-тип image/png входит в список допустимых.
     */
    public function testValidPngFileReturnsData(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload',
            ['title' => 'My Avatar'],
            ['avatar' => $this->createPngFile()],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('image/png', $data['avatar_mime']);
    }

    /**
     * Файл не передан, поле avatar обязательно (NotNull).
     * Violation для поля avatar.
     */
    public function testMissingRequiredFileReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/file/upload', ['title' => 'My Avatar']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('avatar', $paths);
    }

    /**
     * Файл недопустимого MIME-типа (text/plain вместо image/*).
     * Violation для поля avatar.
     */
    public function testInvalidMimeTypeFileReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload',
            ['title' => 'My Avatar'],
            ['avatar' => $this->createTextFile()],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('avatar', $paths);
    }

    /**
     * Файл превышает допустимый размер (maxSize: '1M').
     * Violation для поля avatar.
     */
    public function testOversizedFileReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload',
            ['title' => 'My Avatar'],
            ['avatar' => $this->createOversizedFile()],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('avatar', $paths);
    }

    /**
     * Файл валиден, но title не передан.
     * Violation только для поля title, не для avatar.
     */
    public function testValidFileButMissingTitleReceivesViolationForTitleOnly(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload',
            [],
            ['avatar' => $this->createJpegFile()],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('title', $paths);
        $this->assertNotContains('avatar', $paths);
    }

    /**
     * Оба поля невалидны: файл отсутствует, title пустой.
     * Violations для обоих полей.
     */
    public function testMissingFileAndBlankTitleReceivesViolationsForBothFields(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/file/upload', ['title' => '']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('title', $paths);
        $this->assertContains('avatar', $paths);
    }

    // =========================================================================
    // Nullable файл (без NotNull)
    // =========================================================================

    /**
     * Nullable файл передан и валиден: объект собирается с файлом.
     */
    public function testNullableFileProvidedReturnsDataWithFile(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload-nullable',
            ['title' => 'My Avatar'],
            ['avatar' => $this->createJpegFile()],
        );

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('My Avatar', $data['title']);
        $this->assertTrue($data['has_avatar']);
    }

    /**
     * Nullable файл не передан: свойство avatar получает null без ошибки валидации.
     */
    public function testNullableFileMissingReceivesNullWithoutViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request('POST', '/file/upload-nullable', ['title' => 'My Avatar']);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse($browser);

        $this->assertSame('My Avatar', $data['title']);
        $this->assertFalse($data['has_avatar']);
    }

    /**
     * Nullable файл передан, но с недопустимым MIME-типом.
     * Violation для поля avatar, несмотря на nullable.
     */
    public function testNullableFileWithInvalidMimeTypeReceivesViolation(): void
    {
        $browser = $this->createBrowser();
        $browser->request(
            'POST',
            '/file/upload-nullable',
            ['title' => 'My Avatar'],
            ['avatar' => $this->createTextFile()],
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getJsonResponse($browser);

        $paths = array_column($data['errors'], 'property');

        $this->assertContains('avatar', $paths);
    }
}
