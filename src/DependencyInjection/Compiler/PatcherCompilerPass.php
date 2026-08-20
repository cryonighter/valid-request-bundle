<?php

namespace Cryonighter\ValidRequestBundle\DependencyInjection\Compiler;

use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Cryonighter\ValidRequestBundle\Service\PayloadExtractor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PatcherCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(PayloadExtractor::class)) {
            return;
        }

        $references = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || $definition->isSynthetic()) {
                continue;
            }

            $class = $definition->getClass();

            if ($class === null || !class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, PatcherInterface::class)) {
                $references[] = new Reference($id);
            }
        }

        $container->getDefinition(PayloadExtractor::class)
            ->setArgument('$patchers', $references);
    }
}
