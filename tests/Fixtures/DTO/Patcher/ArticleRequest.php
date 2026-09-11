<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Patcher;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Tests\Fixtures\Patcher\SlugPatcher;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidRequest]
readonly class ArticleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,

        #[BindRequestParam(patcher: SlugPatcher::class)]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,
    ) {}
}
