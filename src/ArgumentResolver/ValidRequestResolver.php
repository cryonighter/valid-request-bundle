<?php

namespace Cryonighter\ValidRequestBundle\ArgumentResolver;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Schema\SchemaFactory;
use Cryonighter\ValidRequestBundle\Service\ObjectHydrator;
use Cryonighter\ValidRequestBundle\Service\PayloadExtractor;
use Cryonighter\ValidRequestBundle\Service\ViolationListStorage;
use Cryonighter\ValidRequestBundle\Service\ViolationTransformer;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use UnexpectedValueException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly final class ValidRequestResolver implements ValueResolverInterface
{
    private const string EXPRESSION_PREFIX = 'expr:';

    public function __construct(
        private ExpressionLanguage $expressionLanguage,
        private ObjectHydrator $objectHydrator,
        private PayloadExtractor $payloadExtractor,
        private SchemaFactory $schemaFactory,
        private ValidatorInterface $validator,
        private ViolationTransformer $violationTransformer,
        private ViolationListStorage $violationListStorage,
    ) {
    }

    /**
     * @throws ReflectionException
     * @throws ValidationFailedException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $className = $argument->getType();

        if (!class_exists($className) && !interface_exists($className)) {
            return [];
        }

        if ($argument->getAttributesOfType(ValueResolver::class)) {
            return [];
        }

        if (is_a($className, ConstraintViolationListInterface::class, true)) {
            $violations = $this->violationListStorage->extract($request, $argument);

            yield ($violations !== null || $argument->isNullable()) ? $violations : new ConstraintViolationList([]);

            return [];
        }

        $reflectionClass = new ReflectionClass($className);

        $validRequestAttribute = $this->getValidRequestAttribute($reflectionClass, $argument);

        if (!$validRequestAttribute) {
            return [];
        }

        $schema = $this->schemaFactory->create($reflectionClass->getName());
        $groups = $validRequestAttribute->groups ?: $this->getDefaultGroups($reflectionClass);
        $groups = $this->resolveExpressionGroups($groups, $request);

        $validationGroups = new ValidationGroups($groups);
        $hasViolationsArg = $this->violationListStorage->hasViolationsArg($request, $argument);

        try {
            // Извлекает данные из запроса и проводит минимальную валидацию, чтоб собрать объект.
            $data = $this->payloadExtractor->extractValidPayload(
                $schema,
                $validRequestAttribute,
                $request,
                $validationGroups,
                $argument->isNullable(),
            );
        } catch (ValidationFailedException $exception) {
            if (!$hasViolationsArg) {
                throw $exception;
            }

            $this->violationListStorage->save($request, $argument, $exception->getViolations());

            yield $argument->isNullable() ? null : $reflectionClass->newInstanceWithoutConstructor();

            return [];
        }

        if ($data === null) {
            yield null;

            return [];
        }

        $dto = $this->objectHydrator->hydrate($schema, $data, $validationGroups, $request);

        // Вторая валидация для правил, требующих объекты
        $violations = $this->validator->validate($dto, groups: $groups);

        if ($violations->count() != 0) {
            $schemaObjectMap = $schema->getObjectMap($validationGroups);

            // Меняем пути к ошибкам с названием свойств класса на названия параметров запроса
            $violations = $this->violationTransformer->propertyToField($violations, $schemaObjectMap);

            if (!$hasViolationsArg) {
                throw new ValidationFailedException($dto, $violations);
            }

            $this->violationListStorage->save($request, $argument, $violations);
        }

        yield $dto;
    }

    /**
     * Вычисляет группы-выражения.
     *
     * Выражением считается объект Expression или строка с префиксом "expr:".
     * Все остальные строки — обычные имена групп.
     *
     * @return array<string|GroupSequence>
     */
    private function resolveExpressionGroups(string|GroupSequence|Expression|array $groups, Request $request): array
    {
        $arrayGroups = !is_array($groups) ? [$groups] : $groups;

        $resolvedGroups = [];

        foreach ($arrayGroups as $group) {
            if ($group instanceof GroupSequence) {
                $resolvedGroups[] = new GroupSequence($this->resolveExpressionGroups($group->groups, $request));

                continue;
            }

            if (is_array($group)) {
                array_push($resolvedGroups, ...$this->resolveExpressionGroups($group, $request));

                continue;
            }

            if (is_string($group) && str_starts_with($group, self::EXPRESSION_PREFIX)) {
                $group = new Expression(trim(substr($group, strlen(self::EXPRESSION_PREFIX))));
            }

            if (!$group instanceof Expression) {
                $resolvedGroups[] = $group;

                continue;
            }

            $result = $this->expressionLanguage->evaluate($group, ['request' => $request]);

            foreach ((array) $result as $item) {
                if (!is_string($item)) {
                    throw new UnexpectedValueException(
                        sprintf(
                        'Expression "%s" must evaluate to a string or an array of strings, "%s" given.',
                            $group,
                            get_debug_type($result),
                        ),
                    );
                }

                $resolvedGroups[] = $item;
            }
        }

        return $resolvedGroups;
    }

    private function getDefaultGroups(ReflectionClass $reflectionClass): array
    {
        return array_filter(['Default', $this->getGroupSequence($reflectionClass)]);
    }

    private function getGroupSequence(ReflectionClass $reflectionClass): ?Assert\GroupSequence
    {
        return array_find(
            $reflectionClass->getAttributes(),
            fn (ReflectionAttribute $attribute): bool => is_a($attribute->getName(), Assert\GroupSequence::class, true),
        )?->newInstance();
    }

    private function getValidRequestAttribute(ReflectionClass $reflectionClass, ArgumentMetadata $argument): ?ValidRequest
    {
        /** @var ValidRequest $validRequest */
        $validRequest = $argument->getAttributesOfType(ValidRequest::class)[0] ?? null;

        if ($validRequest) {
            return $validRequest;
        }

        /** @var ValidRequest $validRequest */
        $validRequest = ($reflectionClass->getAttributes(ValidRequest::class)[0] ?? null)?->newInstance();

        if ($validRequest) {
            return $validRequest;
        }

        return null;
    }
}
