<?php

namespace Cryonighter\ValidRequestBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class NoExtraFields extends Constraint
{
    public function __construct(
        public string $message = 'This field was not expected.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return NoExtraFieldsValidator::class;
    }
}
