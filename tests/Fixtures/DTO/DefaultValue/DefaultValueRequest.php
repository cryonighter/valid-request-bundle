<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки поведения полей со значениями по умолчанию.
 *
 * Правила:
 * - Если поле отсутствует в запросе — свойство принимает значение по умолчанию.
 * - Если поле присутствует в запросе с явным null (JSON) или без значения (form)
 *   и свойство nullable — свойство принимает null, игнорируя значение по умолчанию.
 * - Если поле присутствует в запросе с явной пустой строкой — свойство принимает
 *   пустую строку, игнорируя значение по умолчанию.
 */
#[ValidRequest]
readonly class DefaultValueRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        public string $role = 'user',

        public ?string $comment = 'default comment',

        public int $priority = 5,
    ) {}
}
