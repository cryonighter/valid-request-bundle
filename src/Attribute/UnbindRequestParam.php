<?php

namespace Cryonighter\ValidRequestBundle\Attribute;

use Attribute;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class UnbindRequestParam
{
    private(set) ValidationGroups $validationGroups;

    /**
     * @param string|GroupSequence|array|null $groups The validation groups to apply to the parameter
     */
    public function __construct(
        public readonly string|GroupSequence|array|null $groups = null,
    ) {
        $this->validationGroups = new ValidationGroups($this->groups);
    }
}
