<?php

namespace Cryonighter\ValidRequestBundle\Schema;

use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;

readonly final class PropertyNestedItem
{
    public function __construct(
        public ValidationGroups $groupsAll,
        public ValidationGroups $groupsType,
        public array $types,
        public array $schemas,
    ) {
    }
}
