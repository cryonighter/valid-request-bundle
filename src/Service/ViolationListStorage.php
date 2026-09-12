<?php

namespace Cryonighter\ValidRequestBundle\Service;

use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ViolationListStorage
{
    private const string ARG_RES = 'ARG_RES';

    public function extract(Request $request, ArgumentMetadata $argument): ?ConstraintViolationListInterface
    {
        if (!$request->attributes->has(self::ARG_RES)) {
            return null;
        }

        $cache = $request->attributes->get(self::ARG_RES);
        $controller = $request->attributes->get('_controller');

        $controllerArgs = $this->getControllerArgs($controller);

        $argIndex = $this->getArgIndex($controllerArgs, $argument);
        $validatedArgName = $controllerArgs[$argIndex-1]['name'] ?? null;

        if ($validatedArgName && isset($cache[$controller][$validatedArgName])) {
            $violations = $cache[$controller][$validatedArgName];

            unset($cache[$controller][$validatedArgName]);

            if (empty($cache[$controller])) {
                unset($cache[$controller]);
            }

            $request->attributes->set(self::ARG_RES, $cache);

            return $violations;
        }

        return null;
    }

    public function hasViolationsArg(Request $request, ArgumentMetadata $argument): bool
    {
        $controller = $request->attributes->get('_controller');
        $controllerArgs = $this->getControllerArgs($controller);

        $argIndex = $this->getArgIndex($controllerArgs, $argument);
        $violationsArgType = $controllerArgs[$argIndex + 1]['type'] ?? null;

        // Если следующий за текущим аргумент является списком ограничений
        if ($violationsArgType && $this->isViolationType($violationsArgType)) {
            return true;
        }

        // Или если один из последующих аргументов им является
        // Но только если он один, иначе не получится определить, какой список ограничений к какому DTO относится
        $controllerViolationsArgs = array_filter(
            array_slice($controllerArgs, $argIndex + 2),
            fn(array $arg): bool => $this->isViolationType($arg['type']),
        );

        return match (count($controllerViolationsArgs)) {
            0 => false,
            1 => true,
            default => throw new LogicException('More than one violations argument found'),
        };
    }

    public function save(Request $request, ArgumentMetadata $argument, ConstraintViolationListInterface $violations): void
    {
        $cache = $request->attributes->get(self::ARG_RES) ?? [];
        $controller = $request->attributes->get('_controller');

        $cache[$controller][$argument->getName()] = $violations;

        $request->attributes->set(self::ARG_RES, $cache);
    }

    private function getControllerArgs(string $controller): array
    {
        return array_map(
            fn(ReflectionParameter $parameter): array => [
                'name' => $parameter->getName(),
                'type' => (function (?ReflectionType $type): string {
                    if (!$type) {
                        return 'mixed';
                    }

                    if ($type instanceof ReflectionNamedType) {
                        return $type->getName();
                    }

                    // Если это union-тип, то нужно определить,
                    // есть ли среди них ConstraintViolationListInterface
                    $violationType = array_find(
                        $type->getTypes(),
                        function (ReflectionType $type): bool {
                            if ($type instanceof ReflectionNamedType) {
                                return $this->isViolationType($type->getName());
                            }

                            return false;
                        }
                    );

                    return $violationType ? $violationType->getName() : 'union';
                })($parameter->getType()),
            ],
            ReflectionMethod::createFromMethodName($controller)->getParameters(),
        );
    }

    private function isViolationType(string $type): bool
    {
        return is_a($type, ConstraintViolationListInterface::class, true);
    }

    private function getArgIndex(array $controllerArgs, ArgumentMetadata $argument): ?int
    {
        return array_find_key($controllerArgs, fn(array $arg): bool => $argument->getName() == $arg['name']);
    }
}
