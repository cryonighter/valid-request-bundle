<?php

namespace Cryonighter\ValidRequestBundle\Schema\Exception;

use RuntimeException;

final class CyclicSchemaException extends RuntimeException
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
