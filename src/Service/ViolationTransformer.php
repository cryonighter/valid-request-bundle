<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ViolationTransformer
{
    public function arrayKeyToObjectKey(ConstraintViolationListInterface $violations): ConstraintViolationListInterface
    {
        $newViolations = new ConstraintViolationList([]);

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();

            // Может выглядеть как [field][subfield]
            if ($propertyPath[0] == '[') {
                $propertyPath = substr(str_replace('][', '.', $propertyPath), 1, -1);
            }

            $newViolations->add(
                new ConstraintViolation(
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getParameters(),
                    $violation->getRoot(),
                    $propertyPath,
                    $violation->getInvalidValue(),
                    $violation->getPlural(),
                    $violation->getCode(),
                    $violation->getConstraint(),
                    $violation->getCause(),
                ),
            );
        }

        return $newViolations;
    }
}
