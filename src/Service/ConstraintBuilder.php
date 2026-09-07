<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Schema\ObjectSchema;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use Cryonighter\ValidRequestBundle\Validator\ExtendedCollection;
use Cryonighter\ValidRequestBundle\Validator\NoExtraFields;
use DateTimeInterface;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

final class ConstraintBuilder
{
    public function build(ObjectSchema $schema, ValidationGroups $requestGroups, array $notExpectedFields): ExtendedCollection
    {
        $constraints = [];

        foreach ($schema->properties as $node) {
            $name = $node->fieldName($requestGroups);
            $type = $node->type;

            $notExpected = is_array($notExpectedFields[$name] ?? null) ? $notExpectedFields[$name] : [];

            if (!$type->builtin && $this->checkContainConstraint($node->constraints, [Assert\Valid::class])) {
                $constraints[$name] = $this->build(
                    $node->nested,
                    $requestGroups,
                    $notExpected,
                );

                continue;
            }

            $alls = array_filter($node->constraints, fn(Constraint $constraint): bool => $constraint instanceof Assert\All);

            if ($type->iterable && $alls) {
                $constraints[$name] = [];

                /** @var Assert\All[] $alls */
                foreach ($alls as $all) {
                    $allConstraints = [];

                    foreach ($all->constraints as $constraint) {
                        if ($this->isRequiredAssert($constraint::class)) {
                            if ($constraint instanceof Assert\Type && is_string($constraint->type) && class_exists($constraint->type)) {
                                // Если не задано пользовательское сообщение об ошибке - задаем свое
                                $message = $constraint->message != new Assert\Type($constraint->type)->message
                                    ? $constraint->message
                                    : 'This value should be of type object.';

                                $allConstraints[] = new Assert\Type(
                                    'array',
                                    $message,
                                    $constraint->groups,
                                    $constraint->payload,
                                );

                                $itemSchema = $node->getItemSchemaByClassName($requestGroups, $constraint->type);

                                // Если null - значит группа констрейнта отличается от группы запроса и валидация не нужна
                                if ($itemSchema) {
                                    $notExpected = array_merge(...$notExpected);

                                    $allConstraints[] = $this->build($itemSchema, $requestGroups, $notExpected);
                                }
                            } elseif ($constraint instanceof Assert\Type && is_string($constraint->type) && interface_exists($constraint->type)) {
                                throw new RuntimeException('Interface type is not supported');
                            } elseif ($constraint instanceof Assert\Type && !is_string($constraint->type)) {
                                if (is_null($constraint->type)) {
                                    throw new RuntimeException('Type is not specified');
                                }

                                if (is_array($constraint->type)) {
                                    throw new RuntimeException('Array type is not supported');
                                }
                            } else {
                                $allConstraints[] = $constraint;
                            }
                        }
                    }

                    if (!$allConstraints) {
                        continue;
                    }

                    $constraints[$name][] = new Assert\All($allConstraints);
                }
            }

            $propertyConstraints = [];

            foreach ($node->constraints as $constraint) {
                if ($this->isRequiredAssert($constraint::class)) {
                    $propertyConstraints[] = $constraint;
                }
            }

            if (!$this->checkContainConstraint($propertyConstraints, [Assert\Type::class]) && !$type->isEnum) {
                $message = is_subclass_of($type->name, DateTimeInterface::class)
                    ? 'This value should be of type datetime or timestamp.'
                    : null;

                $propertyConstraints[] = new Assert\Type($type->name, $message);
            }

            if (!$this->checkContainConstraint($propertyConstraints, [Assert\Choice::class]) && $type->isEnum) {
                $propertyConstraints[] = new Assert\Choice(choices: $type->name::cases());
            }

            if (!$this->checkContainConstraint($propertyConstraints, [Assert\NotBlank::class, Assert\NotNull::class])) {
                // defaultValue === null у not nullable типа означает, что значения по умолчанию нет
                if (!$type->nullable && $node->defaultValue === null) {
                    $propertyConstraints[] = new Assert\NotNull();
                }
            }

            if ($propertyConstraints) {
                // Например, у массивов уже есть $constraints[$name] = [new Assert\All()]
                $constraints[$name] = [...$propertyConstraints, ...($constraints[$name] ?? [])];
            }
        }

        // Есть кейсы, где extraFields возможны, поэтому случаи,
        // где они недопустимы, я указываю вручную здесь
        foreach ($notExpectedFields as $name => $value) {
            // Массивы считаем подструктурами, для них NoExtraFields уже установлены в рекурсивных вызовах
            if (!is_array($value)) {
                $constraints[$name] = new NoExtraFields();
            }
        }

        // Я вынужден тут поставить allowMissingFields: true, так как Collection оборачивает все $constraints
        // в Assert\Required, и для NoExtraFields получается парадоксальная ситуация - поле с одной стороны
        // должно быть (требование Required), в с другой его не должно быть (требование NoExtraFields).
        // Но это приводит к тому, что я никогда не получу сообщения "This field is missing",
        // в только "This value should not be blank". Это, с одной стороны, соответствует
        // концепции "запрос валидируется так, будто это уже собранный объект", с другой
        // конечно же хотелось бы отличать кейсы "поля нет" и "поле пустое". Но пока так.
        return new ExtendedCollection($constraints, allowExtraFields: true, allowMissingFields: true);
    }

    /**
     * @param class-string $assertClass
     */
    private function isRequiredAssert(string $assertClass): bool
    {
        $isNotBlank = is_a($assertClass, Assert\NotBlank::class, true);
        $isNotNull = is_a($assertClass, Assert\NotNull::class, true);
        $isType = is_a($assertClass, Assert\Type::class, true);
        $isChoice = is_a($assertClass, Assert\Choice::class, true);

        return $isNotNull || $isNotBlank || $isType || $isChoice;
    }

    private function checkContainConstraint(array $constraints, array $checkedConstraints): bool
    {
        foreach ($checkedConstraints as $checkedConstraint) {
            if (array_find($constraints, fn(Constraint $constraint): bool => is_a($constraint, $checkedConstraint))) {
                return true;
            }
        }

        return false;
    }
}
