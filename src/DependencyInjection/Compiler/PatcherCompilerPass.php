<?php

namespace Cryonighter\ValidRequestBundle\DependencyInjection\Compiler;

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

        foreach ($container->findTaggedServiceIds('cryonighter.valid_request.patcher') as $id => $tags) {
            $references[] = new Reference($id);
        }

        $container->getDefinition(PayloadExtractor::class)
            ->setArgument('$patchers', $references);
    }
}
