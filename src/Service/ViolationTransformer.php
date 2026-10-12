<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ViolationTransformer
{
    public function arrayKeyToObjectKey(ConstraintViolationListInterface $violations, array $objectStruct): ConstraintViolationListInterface
    {
        $newViolations = new ConstraintViolationList([]);

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();

            // Может выглядеть как [field][subfield] или даже [fields][0][subfield]
            if (isset($propertyPath[0]) && $propertyPath[0] === '[') {
                $propertyPath = substr($propertyPath, 1, -1);

                $segments = explode('][', $propertyPath);

                $deep = $objectStruct;
                $result = '';

                foreach ($segments as $segment) {
                    $isList = array_is_list($deep);

                    // $deep[$segment] может быть не определен, если валидация идет по лишнему полю, которого нет в объекте
                    // В этом случае ничего делать не нужно, он попадет в else ветку и сформирует правельный путь к ошибке
                    $deep = $isList ? $deep[0] : ($deep[$segment] ?? null);

                    // Индексные массивы и ассоциативные массивы, не являющиеся объектами, оставляем в скобках
                    if (ctype_digit($segment) || $isList) {
                        $result .= '[' . $segment . ']';
                    } else {
                        $result .= ($result === '' ? '' : '.') . $segment;
                    }
                }

                $propertyPath = $result;
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

    public function propertyToField(ConstraintViolationListInterface $violations, array $objectMap): ConstraintViolationListInterface
    {
        $newViolations = new ConstraintViolationList([]);

        foreach ($violations as $violation) {
            $propertyPath = explode('.', $violation->getPropertyPath());

            $map = $objectMap;

            foreach ($propertyPath as $key => $path) {
                // Если $path содержит индекс, например contacts[0]
                $matches = [];
                preg_match('/(.+)(\[\d+])$/', $path, $matches);

                if ($matches) {
                    $path = $matches[1];
                }

                // Элемента может не быть (например, валидация лишних полей)
                if (empty($map[$path])) {
                    break;
                }

                $map = $map[$path];

                // Так же элемент может быть массивом, если исследование структуры
                // пошло дальше,чем нужно для валидации (например, UploadedFile)
                if (!is_scalar($map)) {
                    continue;
                }

                $propertyPath[$key] = $map;

                // Возвращаем индекс
                if ($matches) {
                    $propertyPath[$key] .= $matches[2];
                }
            }

            $newViolations->add(
                new ConstraintViolation(
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getParameters(),
                    $violation->getRoot(),
                    implode('.', $propertyPath),
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
