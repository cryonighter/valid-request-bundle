<?php

namespace Cryonighter\ValidRequestBundle\ArgumentResolver;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Service\ObjectCollector;
use Cryonighter\ValidRequestBundle\Service\PayloadExtractor;
use Cryonighter\ValidRequestBundle\Service\ViolationListStorage;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class ValidRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private ObjectCollector $objectCollector,
        private PayloadExtractor $payloadExtractor,
        private ValidatorInterface $validator,
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

        $hasViolationsArg = $this->violationListStorage->hasViolationsArg($request, $argument);

        try {
            // Извлекает данные из запроса и проводит минимальную валидацию, чтоб собрать объект.
            $data = $this->payloadExtractor->extractValidPayload($reflectionClass, $validRequestAttribute, $request, $argument->isNullable());
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

        $dto = $this->objectCollector->collect($reflectionClass, $data, $validRequestAttribute);
        $groups = $this->payloadExtractor->resolveGroups($reflectionClass, $validRequestAttribute);

        // Вторая валидация для правил, требующих объекты
        $violations = $this->validator->validate($dto, groups: $groups);

        if ($violations->count() != 0) {
            if (!$hasViolationsArg) {
                throw new ValidationFailedException($dto, $violations);
            }

            $this->violationListStorage->save($request, $argument, $violations);
        }

        yield $dto;
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
