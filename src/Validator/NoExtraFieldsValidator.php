<?php

namespace Cryonighter\ValidRequestBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NoExtraFieldsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoExtraFields) {
            throw new UnexpectedTypeException($constraint, NoExtraFields::class);
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
