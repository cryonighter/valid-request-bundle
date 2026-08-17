<?php

namespace Cryonighter\ValidRequestBundle\Tests\App;

use Cryonighter\ValidRequestBundle\ValidRequestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new ValidRequestBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import(__DIR__ . '/config/framework.yaml');

        $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure()
            ->public()
            ->load(
                'Cryonighter\\ValidRequestBundle\\Tests\\Fixtures\\Controller\\',
                __DIR__ . '/../Fixtures/Controller/',
            )
            ->load(
                'Cryonighter\\ValidRequestBundle\\Tests\\Fixtures\\Patcher\\',
                __DIR__ . '/../Fixtures/Patcher/',
            );
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(
            __DIR__ . '/../Fixtures/Controller/',
            'attribute',
        );
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/valid_request_bundle_tests/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/valid_request_bundle_tests/log';
    }
}
