<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\DTO\DefaultValue;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO для проверки того, когда nullable-аргумент контроллера получает null,
 * если у части свойств есть значения по умолчанию.
 *
 * Правила:
 * - Если в запросе нет ни одного поля DTO (ни в основном, ни в другом источнике),
 *   nullable-аргумент получает null, валидация не выполняется.
 *   Значения по умолчанию входными данными не считаются.
 * - Если в запросе есть хотя бы одно поле DTO (даже пустое, null или совпадающее
 *   со значением по умолчанию), данные считаются входными и проходят валидацию.
 */
#[ValidRequest]
readonly class NullableDefaultValueRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,

        public string $role = 'user',

        public ?string $comment = 'default comment',

        public int $priority = 5,

        #[BindRequestParam(source: RequestValueSource::query)]
        public int $page = 1,
    ) {}
}
