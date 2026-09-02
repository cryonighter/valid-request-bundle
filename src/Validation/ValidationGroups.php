<?php

namespace Cryonighter\ValidRequestBundle\Validation;

use RuntimeException;
use Symfony\Component\Validator\Constraints\GroupSequence;

final readonly class ValidationGroups
{
    /**
     * @var string[]
     */
    public array $groups;

    public function __construct(string|GroupSequence|array|null $groups)
    {
        $this->groups = $this->normalize($groups);
    }

    public function intersects(self $other): bool
    {
        return (bool) array_intersect($this->groups, $other->groups);
    }

    /**
     * @return string[]
     */
    private function normalize(string|GroupSequence|array|null $groups): array
    {
        return match (true) {
            is_string($groups) && $groups => [$groups],
            is_array($groups) && $groups => $this->normalizeArray($groups),
            $groups instanceof GroupSequence => match (true) {
                is_string($groups->groups) && $groups->groups => [$groups->groups],
                is_array($groups->groups) && $groups->groups => $this->normalizeArray($groups->groups),
                $groups->groups instanceof GroupSequence => throw new RuntimeException('Too much nesting of groups'),
                default => ['Default'],
            },
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
        return match (array_all($groups, static fn (mixed $value): bool => is_string($value))) {
            true => $groups,
            default => throw new RuntimeException('Invalid groups format'),
        };
    }
}
