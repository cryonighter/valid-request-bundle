<?php

namespace Cryonighter\ValidRequestBundle\Service;

use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

class ReflectionUtilService
{
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
