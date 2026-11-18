<?php

namespace Cryonighter\ValidRequestBundle\Validation;

use Symfony\Component\Validator\Constraints\GroupSequence;

final readonly class ValidationGroups
{
    /**
     * @var string[]
     */
    public array $groups;

    public function __construct(string|GroupSequence|array|null $groups)
    {
        $this->groups = $this->flatten($this->normalize($groups));
    }

    public function intersects(self $other): bool
    {
        return (bool) array_intersect($this->groups, $other->groups);
    }

    /**
     * Flattens the normalized groups to a single array of strings.
     *
     * @param array $normalizedGroups
     *
     * @return string[]
     */
    private function flatten(array $normalizedGroups): array
    {
        $flattenGroups = [];

        foreach ($normalizedGroups as $value) {
            if (is_array($value)) {
                $flattenGroups = array_merge($flattenGroups, $this->flatten($value));
            } else {
                $flattenGroups[] = $value;
            }
        }

        return $flattenGroups;
    }

    /**
     * Normalizes the groups to an array of strings.
     *
     * @return string[]
     */
    private function normalize(string|GroupSequence|array|null $groups): array
    {
        return match (true) {
            is_string($groups) && $groups => [$groups],
            is_array($groups) && $groups => $this->normalizeArray($groups),
            $groups instanceof GroupSequence => $this->normalize($groups->groups),
            default => ['Default'],
        };
    }

    /**
     * @param array $groups
     *
     * @return string[]
     */
    private function normalizeArray(array $groups): array
    {
        // Здесь нельзя использовать is_string(...) как first-class callable напрямую,
        // так как array_all() вызывает колбэк с двумя аргументами — значением и ключом
        $isArrayOfStrings = array_all($groups, static fn (mixed $value): bool => is_string($value));

        if (!$isArrayOfStrings) {
            return array_map($this->normalize(...), $groups);
        }

        return $groups;
    }
}
