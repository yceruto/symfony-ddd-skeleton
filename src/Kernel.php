<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private $name;

    public function __construct(string $environment, bool $debug, string $name)
    {
        $this->name = $name;

        parent::__construct($environment, $debug);
    }

    public function getCacheDir(): string
    {
        return ($_SERVER['APP_CACHE_DIR'] ?? $this->getProjectDir().'/var/cache').'/'.$this->name.'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return ($_SERVER['APP_LOG_DIR'] ?? $this->getProjectDir().'/var/log').'/'.$this->name;
    }

    protected function getContainerClass(): string
    {
        return ucfirst($this->name).parent::getContainerClass();
    }

    public function registerBundles(): iterable
    {
        $commonBundles = require $this->getProjectDir().'/config/bundles.php';
        $kernelBundles = require $this->getProjectDir().'/config/'.$this->name.'/bundles.php';

        foreach (array_merge($commonBundles, $kernelBundles) as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->fileExists($this->getProjectDir().'/config/'.$this->name.'/bundles.php');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $this->doConfigureContainer($container);
        $this->doConfigureContainer($container, $this->name);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->doConfigureRoutes($routes);
        $this->doConfigureRoutes($routes, $this->name);
    }

    private function doConfigureContainer(ContainerConfigurator $container, string $name = null): void
    {
        $confDir = $this->getProjectDir().'/config'.($name ? '/'.$name : '');

        $container->import($confDir.'/{packages}/*.yaml');
        $container->import($confDir.'/{packages}/'.$this->environment.'/*.yaml');

        if (is_file($confDir.'/services.yaml')) {
            $container->import($confDir.'/services.yaml');
            $container->import($confDir.'/{services}_'.$this->environment.'.yaml');
        } else {
            $container->import($confDir.'/{services}.php');
        }
    }

    private function doConfigureRoutes(RoutingConfigurator $routes, string $name = null): void
    {
        $confDir = $this->getProjectDir().'/config'.($name ? '/'.$name : '');

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($confDir.'/{routes}/*.yaml');

        if (is_file($confDir.'/routes.yaml')) {
            $routes->import($confDir.'/routes.yaml');
        } else {
            $routes->import($confDir.'/{routes}.php');
        }
    }
}
