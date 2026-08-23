<?php

namespace Cryonighter\ValidRequestBundle\Attribute;

use Attribute;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
readonly class ValidRequest
{
    private(set) ValidationGroups $validationGroups;

    /**
     * @param RequestValueSource $source                   The source of the data
     * @param string|GroupSequence|array|null $groups      The validation groups to apply to the data
     * @param class-string<PatcherInterface>|null $patcher The patcher to apply to the data
     */
    public function __construct(
        public RequestValueSource $source = RequestValueSource::payload,
        public string|GroupSequence|array|null $groups = null,
        public ?string $patcher = null,
    ) {
        if ($this->patcher !== null && !is_a($this->patcher, PatcherInterface::class, true)) {
            throw new InvalidArgumentException('Patcher class "' . $this->patcher . '" must implement PatcherInterface');
        }

        $this->validationGroups = new ValidationGroups($this->groups);
    }
}
