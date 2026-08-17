<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
readonly class FileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $title,

        #[BindRequestParam(source: RequestValueSource::files)]
        #[Assert\File(
            maxSize: '1M',
            mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
            maxSizeMessage: 'File is too large',
            mimeTypesMessage: 'Please upload a valid image',
        )]
        public UploadedFile $avatar,
    ) {}
}
