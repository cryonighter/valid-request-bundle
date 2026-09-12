<?php

namespace Cryonighter\ValidRequestBundle\Validator;

use Symfony\Component\Validator\Constraints\Collection;

final class ExtendedCollection extends Collection
{
    public string $invalidTypeMessage = 'This value must be an object.';

    public function validatedBy(): string
    {
        return ExtendedCollectionValidator::class;
    }
}
