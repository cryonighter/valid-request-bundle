<?php

namespace Cryonighter\ValidRequestBundle\Exception;

final class CyclicSchemaException extends ValidRequestException
{
    /**
     * @param class-string[] $path
     */
    public function __construct(
        public readonly array $path,
    ) {
        parent::__construct(
            'Cyclic DTO reference detected while building schema: ' . implode(' -> ', $path),
        );
    }
}
