<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class VirtualKernel extends Kernel
{
    use MicroKernelTrait;

    protected $name;

    public function __construct($environment, $debug, $name)
    {
        $this->name = $name;

        parent::__construct($environment, $debug);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->name.'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log/'.$this->name;
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

    protected function getContainerClass()
    {
        return $this->name.ucfirst($this->environment).($this->debug ? 'Debug' : '').'Container';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->fileExists('../config/'.$this->name.'/bundles.php');

        $this->doConfigureContainer($loader);
        $this->doConfigureContainer($loader, $this->name);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->doConfigureRoutes($routes);
        $this->doConfigureRoutes($routes, $this->name);
    }

    private function doConfigureContainer(LoaderInterface $loader, string $name = null): void
    {
        $confDir = $this->getProjectDir().'/config'.($name ? '/'.$name : '');

        $loader->load($confDir.'/{packages}/*.yaml', 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/*.yaml', 'glob');
        $loader->load($confDir.'/{services}.yaml', 'glob');
        $loader->load($confDir.'/{'.$this->environment.'}/*.yaml', 'glob');
    }

    private function doConfigureRoutes(RoutingConfigurator $routes, string $name = null): void
    {
        $confDir = $this->getProjectDir().'/config'.($name ? '/'.$name : '');

        $routes->import($confDir.'/{routes}/*.yaml');
        $routes->import($confDir.'/{routes}/'.$this->environment.'/*.yaml');
        $routes->import($confDir.'/{routes}.yaml');
    }
}
