<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Exception\ValidRequestException;
use Cryonighter\ValidRequestBundle\Patcher\PatcherLocator;
use Cryonighter\ValidRequestBundle\Schema\ObjectSchema;
use Cryonighter\ValidRequestBundle\Schema\PropertyNode;
use Cryonighter\ValidRequestBundle\Validation\ValidationGroups;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionProperty;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

readonly class ObjectHydrator
{
    public function __construct(
        private PatcherLocator $patcherLocator,
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function hydrate(ObjectSchema $schema, array $data, ValidationGroups $groups, Request $request): object
    {
        $className = $schema->className;
        $reflectionClass = new ReflectionClass($className);

        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor?->getParameters();

        if ($constructor && $parameters) {
            $arguments = $this->getConstructorArguments($schema, $data, $parameters, $groups, $request);

            $data = array_diff_key($data, $arguments);

            $object = new $className(...array_values($arguments));
        } else {
            $object = new $className();
        }

        if (!$data) {
            return $object;
        }

        $propertyMap = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyMap[$this->getFieldName($property, $groups)] = $property;
        }

        foreach ($data as $name => $value) {
            /** @var ReflectionProperty $property */
            $property = $propertyMap[$name] ?? null;

            // Могут попадаться extra fields, которых нет в классе, но есть в запросе
            // Для источников данных, отличных от payload, это обычное дело
            if (!$property) {
                continue;
            }

            $propertyName = $property->getName();
            $propertyNode = $schema->properties[$propertyName];

            $value = $this->hydrateValue($name, $value, $propertyNode, $groups, $request);

            $setter = 'set' . ucfirst($propertyName);

            if ($reflectionClass->hasMethod($setter)) {
                $reflectionSetter = $reflectionClass->getMethod($setter);

                if ($reflectionSetter->isPublic() && $reflectionSetter->getNumberOfParameters() === 1) {
                    $object->$setter($value);

                    continue;
                }
            }

            if ($property->isPublic() && !$property->isReadOnly()) {
                $object->$propertyName = $value;

                continue;
            }

            // Если сеттера нет, а свойство приватное или readonly, то используем рефлексию
            $property->setValue($object, $value);
        }

        return $object;
    }

    /**
     * @param ObjectSchema          $schema
     * @param array                 $payload
     * @param ReflectionParameter[] $parameters
     * @param ValidationGroups      $groups
     * @param Request               $request
     *
     * @return array
     *
     * @throws ReflectionException
     */
    private function getConstructorArguments(
        ObjectSchema $schema,
        array $payload,
        array $parameters,
        ValidationGroups $groups,
        Request $request,
    ): array {
        $arguments = [];

        foreach ($parameters as $parameter) {
            $name = $this->getFieldName($parameter, $groups);
            $value = $payload[$name] ?? null;

            // Если несколько аргументов конструктора биндятся к одному и тому же параметру запроса
            if (isset($arguments[$name])) {
                // Порядок и количество аргументов важно сохранить для передачи в конструктор
                // А имена используются для вычесления разници между свойствами, инициализируемыми
                // в конструкторе и свойствами, инициируемые другими способами
                $arguments[$parameter->name] = $arguments[$name];

                continue;
            }

            $patcher = $this->getBindRequestParamByGroups($parameter, $groups)?->patcher;

            if ($patcher && !$parameter->isPromoted()) {
                $arguments[$name] = $this->patcherLocator->get($patcher)->patch($value, $request);

                continue;
            }

            $propertyNode = $schema->getPropertyByRequestName($name, $groups);

            // Параметр конструктора не сопоставляется со свойством класса ни по имени, ни по бинду
            if (!$propertyNode) {
                if ($value === null) {
                    if ($parameter->getType()->allowsNull()) {
                        $arguments[$name] = null;

                        continue;
                    }

                    if ($parameter->isDefaultValueAvailable()) {
                        $arguments[$name] = $parameter->getDefaultValue();

                        continue;
                    }

                    // Наличие этого кейса спорно. Он появился при другой архитектуре и не понятно нужен ли сейчас.
                    if (in_array($parameter->getType()->getName(), ['array', 'callable'])) {
                        $arguments[$name] = [];

                        continue;
                    }

                    throw new ValidRequestException("The '$name' constructor parameter must be non-empty, nullable, or have a default value");
                }

                $arguments[$name] = $value;

                continue;
            }

            $arguments[$name] = $this->hydrateValue($name, $value, $propertyNode, $groups, $request);
        }

        return $arguments;
    }

    /**
     * @throws ReflectionException
     */
    private function hydrateValue(string $name, mixed $value, PropertyNode $propertyNode, ValidationGroups $groups, Request $request): mixed
    {
        $type = $propertyNode->type;

        if ($value === null) {
            if ($type->nullable) {
                return null;
            }

            if ($propertyNode->defaultValue) {
                return $propertyNode->defaultValue;
            }

            // Наличие этого кейса спорно. Он появился при другой архитектуре и не понятно нужен ли сейчас.
            if (in_array($type->name, ['array', 'callable'])) {
                return [];
            }
        }

        if ($type->isObject || $type->name === 'mixed') {
            $nestedSchema = $propertyNode->getNestedSchema($groups);
            $nestedType = $propertyNode->getNestedTypes($groups)[0] ?? null;

            // Типы "object" и "mixed" могут как иметь схему (через Assert\Type) и быть объектами, так и не иметь и быть примитивами.
            // Не встроенные типы (классы, интерфейсы, кроме stdClass) схему иметь обязаны, если ее по каким то причинам нет - бросаем исключение.
            // Если значение уже является объектом (например, его уже построил кастер) - пропускаем
            if (($nestedSchema || !$type->builtin || $type->name === 'object' || ($type->name === 'mixed' && $nestedType === 'object')) && !is_object($value)) {
                // Данные уже провалидированы, так что такое вряд ли может быть. Но на всякий случай добавлю.
                if (!is_array($value)) {
                    throw new ValidRequestException(
                        'Hydrating an object of type "' .
                        ($nestedSchema?->className ?? $nestedType ?? $type->name) .
                        '" requires a list of its properties as an array, not a ' . gettype($value),
                    );
                }

                if (!$nestedSchema || $nestedSchema->className === stdClass::class) {
                    // JSON encode/decode нужно для преобразования вложенных массивов в объект
                    // Дополнительный каст к объекту нужен для преобразования пустого массива
                    if (in_array($type->name, ['mixed', 'object', stdClass::class])) {
                        return (object) json_decode(json_encode($value));
                    }

                    throw new ValidRequestException("Unable to resolve class for param '$name'");
                }

                return $this->hydrate($nestedSchema, $value, $groups, $request);
            }
        }

        if (in_array($type->name, ['array', 'iterable']) && !is_object($value)) {
            $itemSchema = $propertyNode->getItemSchema($groups);

            if ($itemSchema) {
                return array_map(
                    fn(array $item): object => $this->hydrate($itemSchema, $item, $groups, $request),
                    is_array($value) ? $value : iterator_to_array($value),
                );
            }

            if (!is_iterable($value)) {
                throw new ValidRequestException("The '$propertyNode->propertyName' property must be an iterable, not a " . gettype($value));
            }
        }

        // Валидные nullable кейсы к этому моменту уже отработаны
        return $value ?? throw new ValidRequestException("The '$propertyNode->propertyName' property must be non-empty, nullable or have a default value");
    }

    private function getFieldName(ReflectionProperty|ReflectionParameter $property, ValidationGroups $groups): string
    {
        return $this->getBindRequestParamByGroups($property, $groups)?->name ?: $property->getName();
    }

    private function getBindRequestParamByGroups(
        ReflectionProperty|ReflectionParameter $property,
        ValidationGroups $groups,
    ): ?BindRequestParam {
        return array_find(
            array_map(
                function(ReflectionAttribute $attribute) use ($property): BindRequestParam {
                    /** @var BindRequestParam $bindRequestParam */
                    $bindRequestParam = $attribute->newInstance();

                    if ($bindRequestParam->source && $property instanceof ReflectionParameter && !$property->isPromoted()) {
                        throw new ValidRequestException("BindRequestParam::source cannot be defined on a simple (not promoted) constructor parameter");
                    }

                    return $bindRequestParam;
                },
                $property->getAttributes(BindRequestParam::class),
            ),
            fn(BindRequestParam $bindPayload): bool => $groups->intersects($bindPayload->validationGroups),
        );
    }
}
