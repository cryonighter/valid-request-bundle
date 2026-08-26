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

            // Может выглядеть как [field][subfield] или даже [fields][0][subfield]
            if (isset($propertyPath[0]) && $propertyPath[0] === '[') {
                $propertyPath = substr($propertyPath, 1, -1);

                $segments = explode('][', $propertyPath);

                $result = '';
                foreach ($segments as $segment) {
                    if (ctype_digit($segment)) {
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

            foreach ($propertyPath as $key => $path) {
                // Если $path содержит индекс, например contacts[0]
                $matches = [];
                preg_match('/(.+)(\[\d+])$/', $path, $matches);

                if ($matches) {
                    $path = $matches[1];
                }

                // Элемента может не быть (например, валидация лишних полей)
                // Элемент может быть массивом, если исследование структуры пошло дальше, чем нужно для валидации (например, UploadedFile)
                if (empty($objectMap[$path]) || !is_scalar($objectMap[$path])) {
                    break;
                }

                $propertyPath[$key] = $objectMap[$path];

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

//    public function propertyToField(ConstraintViolationListInterface $violations, array $objectMap): ConstraintViolationListInterface
//    {
//        $newViolations = new ConstraintViolationList([]);
//
//        foreach ($violations as $violation) {
//            $propertyPath = explode('.', $violation->getPropertyPath());
//
//            $currentMap = $objectMap;
//
//            foreach ($propertyPath as $key => $path) {
//                // Если $path содержит индекс, например contacts[0]
//                $matches = [];
//                preg_match('/(.+)(\[\d+])$/', $path, $matches);
//
//                if ($matches) {
//                    $path = $matches[1];
//                }
//
//                // Элемента может не быть (например, валидация лишних полей)
//                if (!isset($currentMap[$path])) {
//                    break;
//                }
//
//                $mapped = $currentMap[$path];
//
//                // Лист карты — это fieldName-строка: переименовываем и дальше спускаться некуда
//                if (is_scalar($mapped)) {
//                    $propertyPath[$key] = $mapped;
//
//                    // Возвращаем индекс
//                    if ($matches) {
//                        $propertyPath[$key] .= $matches[2];
//                    }
//
//                    break;
//                }
//
//                // Под-карта (вложенный объект или элемент массива объектов):
//                // имя самого свойства не переименовываем (для массива объектов имя поля
//                // формируется отдельно), но спускаемся глубже для следующего сегмента
//                if ($matches) {
//                    // Возвращаем индекс к текущему сегменту без переименования базы
//                    $propertyPath[$key] = $path . $matches[2];
//                }
//
//                $currentMap = $mapped;
//            }
//
//            $newViolations->add(
//                new ConstraintViolation(
//                    $violation->getMessage(),
//                    $violation->getMessageTemplate(),
//                    $violation->getParameters(),
//                    $violation->getRoot(),
//                    implode('.', $propertyPath),
//                    $violation->getInvalidValue(),
//                    $violation->getPlural(),
//                    $violation->getCode(),
//                    $violation->getConstraint(),
//                    $violation->getCause(),
//                ),
//            );
//        }
//
//        return $newViolations;
//    }
}
