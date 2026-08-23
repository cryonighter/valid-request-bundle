<?php

namespace Cryonighter\ValidRequestBundle\Patcher;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

readonly class PatcherLocator
{
    public function __construct(
        private ContainerInterface $locator,
    ) {
    }

    /**
     * @param class-string<PatcherInterface> $class
     */
    public function get(string $class): PatcherInterface
    {
        try {
            return $this->locator->get($class);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            throw new RuntimeException('Patcher service "' . $class . '" not found in container', 0, $exception);
        }
    }
}
