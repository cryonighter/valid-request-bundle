<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\File;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки nullable-файла: если файл не передан,
 * свойство получает null без ошибки валидации.
 */
#[ValidRequest]
readonly class NullableFileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $title,

        #[BindRequestParam(source: RequestValueSource::files)]
        #[Assert\File(
            maxSize: '1M',
            mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        )]
        public ?UploadedFile $avatar,
    ) {}
}
