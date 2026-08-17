<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки поведения бандла при наличии лишних полей в запросе.
 * Бандл автоматически применяет внутренний constraint NoExtraFields
 * ко всем полям payload-запроса, которые не объявлены в классе.
 * Для других источников (query, headers и т.д.) лишние поля игнорируются.
 */
#[ValidRequest]
readonly class ExtraFieldsRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
    ) {}
}
