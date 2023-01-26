<?php
declare(strict_types=1);

namespace Attlaz\Project\DI;

use Attlaz\Adapter\Base\Model\Connection\AdapterRegistrar;
use Attlaz\Project\App\Config;
use Attlaz\Project\Connections\AdapterFactory;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;

class AdapterDILoader
{

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function initDI(ContainerBuilder $containerBuilder, Config $config): void
    {

        $adapterNames = AdapterRegistrar::getAdapterIds();


        foreach ($adapterNames as $adapterName) {

            $factory = $this->getDIFactory($adapterName);
            if (!\is_null($factory)) {
                $adapterDefinitions = $factory->getDefinitions($config);
                if (count($adapterDefinitions) > 0) {
                    $containerBuilder->addDefinitions($adapterDefinitions);
                }
            }

        }
    }

    private function getDIFactory(string $adapterName): AdapterFactory|null
    {

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterName);


        if (\is_null($adapterFactoryClassName)) {
            throw new \Exception('Unknown connection adapter ' . $adapterName . ' make sure the package is installed');
        }


        /** @var AdapterFactory $adapterFactory */
        $adapterFactory = new $adapterFactoryClassName();

        if (!$adapterFactory instanceof AdapterFactory) {
            return null;
        }
        return $adapterFactory;
    }


}
