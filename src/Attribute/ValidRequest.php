<?php

namespace Cryonighter\ValidRequestBundle\Attribute;

use Attribute;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
readonly final class ValidRequest
{
    /**
     * @param RequestValueSource $source                         The source of the data
     * @param string|GroupSequence|Expression|array|null $groups The validation groups to apply to the data
     * @param class-string<PatcherInterface>|null $patcher       The patcher to apply to the data
     */
    public function __construct(
        public RequestValueSource $source = RequestValueSource::payload,
        public string|GroupSequence|Expression|array|null $groups = null,
        public ?string $patcher = null,
    ) {
        if ($patcher !== null && !is_a($patcher, PatcherInterface::class, true)) {
            throw new InvalidArgumentException('Patcher class "' . $patcher . '" must implement ' . PatcherInterface::class);
        }
    }
}
