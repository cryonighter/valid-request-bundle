<?php

namespace Cryonighter\ValidRequestBundle\Service;

use Cryonighter\ValidRequestBundle\Attribute\BindRequestParam;
use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

class ReflectionUtilService
{
    public function getBindRequestParamByGroups(ReflectionProperty|ReflectionParameter $item, ValidRequest $validRequest): ?BindRequestParam
    {
        return array_find(
            array_map(
                fn(ReflectionAttribute $attribute): BindRequestParam => $attribute->newInstance(),
                $item->getAttributes(BindRequestParam::class),
            ),
            fn(BindRequestParam $bindPayload): bool => $validRequest->validationGroups->intersects($bindPayload->validationGroups),
        );
    }

    public function getFieldName(ReflectionProperty|ReflectionParameter $item, ValidRequest $validRequest): string
    {
        return $this->getBindRequestParamByGroups($item, $validRequest)?->name ?: $item->getName();
    }

    public function getPropertyDefaultValue(ReflectionProperty $property): mixed
    {
        // Promoted property
        foreach ($property->getDeclaringClass()->getConstructor()?->getParameters() ?? [] as $param) {
            if ($param->getName() === $property->getName() && $param->isPromoted()) {
                return $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }

        // Classic property
        return $property->hasDefaultValue() ? $property->getDefaultValue() : null;
    }

    public function getTypeFromAssertAll(ReflectionProperty|ReflectionParameter $property): string|array|null
    {
        if ($property instanceof ReflectionParameter) {
            $property = $this->getPropertyByParameter($property);
        }

        $alls = $property->getAttributes(Assert\All::class);

        if ($alls) {
            /** @var Assert\All[] $allInstances */
            $allInstances = array_map(fn (ReflectionAttribute $all): object => $all->newInstance(), $alls);

            foreach ($allInstances as $all) {
                foreach ($all->constraints as $constraint) {
                    if ($constraint instanceof Assert\Type) {
                        // TODO: выбирает первый попавшийся, не смотря на группы
                        // TODO: тут правельнее делать пересекающийся тип, но они пока не поддерживаются
                        // TODO: но даже этот первый попавшийся тип модет быть массивом или даже null
                        return $constraint->type;
                    }
                }
            }
        }

        return null;
    }

    private function getPropertyByParameter(ReflectionParameter $parameter): ?ReflectionProperty
    {
        return array_find(
            $parameter->getDeclaringClass()->getProperties() ?? [],
            fn(ReflectionProperty $property): bool => $property->getName() === $parameter->getName(),
        );
    }
}
