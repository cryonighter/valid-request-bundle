<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\Nested;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки вложенных объектов и массивов объектов.
 *
 * settings — nullable вложенный объект: при отсутствии данных передаётся null.
 * contacts — массив объектов ContactDTO, валидируется через Assert\All + Assert\Type.
 */
#[ValidRequest]
readonly class ProfileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $username,

        #[Assert\Valid]
        public ?SettingsDTO $settings,

        #[Assert\NotBlank]
        #[Assert\All([
            new Assert\NotBlank(),
            new Assert\Type(ContactDTO::class),
        ])]
        #[Assert\Valid]
        public array $contacts,
    ) {}
}
