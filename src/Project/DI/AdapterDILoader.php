<?php

declare(strict_types=1);

namespace Attlaz\Project\DI;

use Attlaz\Adapter\Base\Model\AdapterDefinitionsCollector;
use Attlaz\Adapter\Base\Model\Connection\AdapterRegistrar;
use Attlaz\Project\App\Config;
use DI\ContainerBuilder;

class AdapterDILoader
{
    public function addDefinitionsToDi(ContainerBuilder $containerBuilder, Config $config): void
    {
        $adapterNames = AdapterRegistrar::getAdapterIds();


        $config->loadConfig();
        foreach ($adapterNames as $adapterName) {
            $adapterDefinitionsCollector = $this->getAdapterDefinitionsCollector($adapterName);
            if (null !== $adapterDefinitionsCollector) {
                $adapterDefinitions = $adapterDefinitionsCollector->getDefinitions($config);
                $containerBuilder->addDefinitions($adapterDefinitions);
            }
        }
    }

    private function getAdapterDefinitionsCollector(string $adapterName): AdapterDefinitionsCollector|null
    {

        $adapterDefinitionsCollectorClass = AdapterRegistrar::getDefinitionsClassName($adapterName);


        if (null === $adapterDefinitionsCollectorClass) {
            return null;
        }

        $adapterDefinitionsCollector = new $adapterDefinitionsCollectorClass();
        if (!$adapterDefinitionsCollector instanceof AdapterDefinitionsCollector) {
            throw new \RuntimeException('Adapter definitions collector `' . $adapterDefinitionsCollectorClass . '` must implements `' . AdapterDefinitionsCollector::class . '`');
        }
        return $adapterDefinitionsCollector;
    }


}
