<?php

namespace Cryonighter\ValidRequestBundle\Attribute;

use Attribute;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use RuntimeException;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER)]
class ValidRequest
{
    private ?array $arrayGroups = null;

    public function __construct(
        public readonly RequestValueSource $source = RequestValueSource::payload,
        public readonly string|GroupSequence|array|null $groups = null,
        public readonly ?string $patcher = null,
    ) {
    }

    public function getArrayGroups(): array
    {
        if ($this->arrayGroups === null) {
            $this->arrayGroups = match (true) {
                is_string($this->groups) && $this->groups => [$this->groups],
                is_array($this->groups) && $this->groups => $this->resolveArrayGroups($this->groups),
                $this->groups instanceof GroupSequence => match (true) {
                    is_string($this->groups->groups) && $this->groups->groups => [$this->groups->groups],
                    is_array($this->groups->groups) && $this->groups->groups => $this->resolveArrayGroups($this->groups->groups),
                    $this->groups->groups instanceof GroupSequence => throw new RuntimeException('Too much nesting of groups'),
                    default => ['Default'],
                },
                default => ['Default'],
            };
        }

        return $this->arrayGroups;
    }

    private function resolveArrayGroups(array $groups): array
    {
        return match (array_all($groups, is_string(...))) {
            true => $groups,
            default => throw new RuntimeException('Invalid groups format'),
        };
    }
}
