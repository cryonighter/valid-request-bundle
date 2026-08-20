<?php

namespace Cryonighter\ValidRequestBundle;

use Cryonighter\ValidRequestBundle\DependencyInjection\Compiler\PatcherCompilerPass;
use Cryonighter\ValidRequestBundle\Patcher\PatcherInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ValidRequestBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(PatcherInterface::class)
            ->addTag('cryonighter.valid_request.patcher');

        $container->addCompilerPass(new PatcherCompilerPass());
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $configurator,
        ContainerBuilder $container,
    ): void {
        $configurator->import('../config/services.yaml');
    }
}
