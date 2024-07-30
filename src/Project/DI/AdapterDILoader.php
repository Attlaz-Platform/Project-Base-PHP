<?php

declare(strict_types=1);

namespace Attlaz\Project\DI;

use Attlaz\Adapter\Base\Model\AdapterFactory;
use Attlaz\Adapter\Base\Model\Connection\AdapterRegistrar;
use Attlaz\Project\App\Config;
use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

class AdapterDILoader
{
    public function __construct(private readonly Container $diContainer)
    {
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @param Config $config
     * @return void
     * @deprecated
     *
     */
    public function initDI(ContainerBuilder $containerBuilder, Config $config): void
    {
//
//        $adapterNames = AdapterRegistrar::getAdapterIds();
//
//
//        $config->loadConfig();
//        foreach ($adapterNames as $adapterName) {
//
//            $factory = $this->getDIFactory($adapterName);
//            if (!\is_null($factory)) {
//                $adapterDefinitions = $factory->getDefinitions($config);
//                if (count($adapterDefinitions) > 0) {
//                    $containerBuilder->addDefinitions($adapterDefinitions);
//                }
//            }
//
//        }
    }

    public function addDefinitionsToDi(Config $config): void
    {
        $adapterNames = AdapterRegistrar::getAdapterIds();


        $config->loadConfig();
        foreach ($adapterNames as $adapterName) {

            $factory = $this->getAdapterFactoryInstance($this->diContainer, $adapterName);
            if (!\is_null($factory)) {
                $adapterDefinitions = $factory->getDefinitions($config);
                if (count($adapterDefinitions) > 0) {
                    foreach ($adapterDefinitions as $key => $value) {
                        $this->diContainer->set($key, $value);
                    }
                }
            }

        }
    }

    private function getAdapterFactoryInstance(ContainerInterface $objectContainer, string $adapterName): AdapterFactory|null
    {

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterName);


        if (\is_null($adapterFactoryClassName)) {
            throw new \Exception('Unknown connection adapter ' . $adapterName . ' make sure the package is installed');
        }

        $adapterFactory = $objectContainer->get($adapterFactoryClassName);

        if (!$adapterFactory instanceof AdapterFactory) {
            throw new \RuntimeException('Adapter factory `' . $adapterFactoryClassName . '` must implements `' . AdapterFactory::class . '`');
        }
        return $adapterFactory;
    }


}
