<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class VirtualKernel extends Kernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,yaml}';

    public function __construct($environment, $debug, $name)
    {
        $this->name = $name;

        parent::__construct($environment, $debug);
    }

    public function getCacheDir(): string
    {
        return dirname(__DIR__).'/var/cache/'.$this->name.'/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__).'/var/log/'.$this->name;
    }

    public function serialize()
    {
        return serialize(array($this->environment, $this->debug, $this->name));
    }

    public function unserialize($data)
    {
        list($environment, $debug, $name) = unserialize($data, array('allowed_classes' => false));

        $this->__construct($environment, $debug, $name);
    }

    public function registerBundles(): iterable
    {
        $commonBundles = require dirname(__DIR__).'/config/bundles.php';
        $kernelBundles = require dirname(__DIR__).'/config/'.$this->name.'/bundles.php';

        foreach (array_merge($commonBundles, $kernelBundles) as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $this->doConfigureContainer($container, $loader);
        $this->doConfigureContainer($container, $loader, $this->name);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $this->doConfigureRoutes($routes);
        $this->doConfigureRoutes($routes, $this->name);
    }

    private function doConfigureContainer(ContainerBuilder $container, LoaderInterface $loader, string $name = null): void
    {
        $confDir = dirname(__DIR__).'/config/'.$name;
        if (is_dir($confDir.'/packages/')) {
            $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');
        }
        if (is_dir($confDir.'/packages/'.$this->environment)) {
            $loader->load($confDir.'/packages/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir.'/services'.self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir.'/'.$this->environment)) {
            $loader->load($confDir.'/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        }
    }

    private function doConfigureRoutes(RouteCollectionBuilder $routes, string $name = null): void
    {
        $confDir = dirname(__DIR__).'/config/'.$name;
        if (is_dir($confDir.'/routes/')) {
            $routes->import($confDir.'/routes/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        if (is_dir($confDir.'/routes/'.$this->environment)) {
            $routes->import($confDir.'/routes/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        $routes->import($confDir.'/routes'.self::CONFIG_EXTS, '/', 'glob');
    }
}
