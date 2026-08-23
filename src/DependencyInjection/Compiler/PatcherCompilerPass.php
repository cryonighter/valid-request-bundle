<?php

namespace Cryonighter\ValidRequestBundle\DependencyInjection\Compiler;

use Cryonighter\ValidRequestBundle\Patcher\PatcherLocator;
use Cryonighter\ValidRequestBundle\Service\PayloadExtractor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class PatcherCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(PayloadExtractor::class)) {
            return;
        }

        $references = [];

        foreach ($container->findTaggedServiceIds('cryonighter.valid_request.patcher') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass() ?? $id;

            $references[$class] = new Reference($id);
        }

        // Общий локатор с патчерами (т.е. сервисами, помеченными тегом)
        $locatorDefinition = new Definition(ServiceLocator::class, [$references]);
        $locatorDefinition->addTag('container.service_locator');

        // Типизированный локатор с патчерами (обертка над общим)
        $patcherLocator = new Definition(PatcherLocator::class, [$locatorDefinition]);

        // Добавляем его в общий контейнер
        $container->setDefinition(PatcherLocator::class, $patcherLocator);

        $container->getDefinition(PayloadExtractor::class)
            ->setArgument('$patcherLocator', new Reference(PatcherLocator::class));
    }
}
