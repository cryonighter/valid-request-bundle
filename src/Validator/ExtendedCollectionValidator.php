<?php

namespace Cryonighter\ValidRequestBundle\Validator;

use ArrayAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\CollectionValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Traversable;

class ExtendedCollectionValidator extends CollectionValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExtendedCollection) {
            throw new UnexpectedTypeException($constraint, ExtendedCollection::class);
        }

        if ($value !== null && !is_array($value) && !($value instanceof Traversable && $value instanceof ArrayAccess)) {
            $this->context->buildViolation($constraint->invalidTypeMessage)
                ->addViolation();
            return;
        }

        parent::validate($value, $constraint);
    }
}
