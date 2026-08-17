<?php

namespace Cryonighter\ValidRequestBundle\Tests\Integration\Concern;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Вспомогательный trait для создания тестовых UploadedFile-объектов
 * без реальной загрузки файлов через HTTP.
 */
trait CreatesUploadedFiles
{
    /**
     * Создаёт временный файл нужного типа и оборачивает в UploadedFile.
     */
    protected function createUploadedFile(
        string $content,
        string $originalName,
        string $mimeType,
    ): UploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'test_upload_');

        file_put_contents($path, $content);

        return new UploadedFile(
            path: $path,
            originalName: $originalName,
            mimeType: $mimeType,
            error: UPLOAD_ERR_OK,
            test: true,
        );
    }

    /**
     * Создаёт минимально валидный JPEG-файл (1x1 пиксель, белый).
     */
    protected function createJpegFile(string $name = 'avatar.jpg'): UploadedFile
    {
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8U'
            . 'HRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgN'
            . 'DRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy'
            . 'MjL/wAARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAA'
            . 'AAAAAAAAAAAAAP/EABQBAQAAAAAAAAAAAAAAAAAAAAD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA'
            . '/9oADAMBAAIRAxEAPwCwABmX/9k=',
        );

        return $this->createUploadedFile($jpeg, $name, 'image/jpeg');
    }

    /**
     * Создаёт минимально валидный PNG-файл (1x1 пиксель, прозрачный).
     */
    protected function createPngFile(string $name = 'avatar.png'): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        );

        return $this->createUploadedFile($png, $name, 'image/png');
    }

    /**
     * Создаёт файл с недопустимым MIME-типом (text/plain).
     */
    protected function createTextFile(string $name = 'document.txt'): UploadedFile
    {
        return $this->createUploadedFile('hello world', $name, 'text/plain');
    }

    /**
     * Создаёт файл, превышающий лимит размера (передаём нужный размер контента).
     */
    protected function createOversizedFile(string $name = 'big.jpg'): UploadedFile
    {
        // Генерируем контент больше 1MB
        $content = str_repeat('x', 1024 * 1024 + 1);

        return $this->createUploadedFile($content, $name, 'image/jpeg');
    }
}
