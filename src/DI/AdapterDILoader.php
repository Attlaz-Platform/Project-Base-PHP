<?php
declare(strict_types=1);


namespace Attlaz\Project\DI;


use Attlaz\Project\App\Config;
use Attlaz\Project\Connections\AdapterFactory;
use Attlaz\Project\Connections\AdapterRegistrar;
use Psr\Log\LoggerInterface;

class AdapterDILoader
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function initDI(\DI\ContainerBuilder $containerBuilder, Config $config): void
    {

        $adapterNames = AdapterRegistrar::getAdapterNames();


        foreach ($adapterNames as $adapterName) {

            $factory = $this->getDIFactory($adapterName);
            if (!\is_null($factory)) {
                $adapterDefinitions = $factory->getDefinitions($config);
                if (!\is_null($adapterDefinitions) && count($adapterDefinitions) > 0) {
                    $containerBuilder->addDefinitions($adapterDefinitions);
                }
            }

        }
    }

    private function getDIFactory(string $adapterName): ?AdapterFactory
    {

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterName);


        if (\is_null($adapterFactoryClassName)) {
            throw new \Exception('Unknown connection adapter ' . $adapterName . ' make sure the package is installed');
        }

        /** @var AdapterFactory $adapterFactory */
        $adapterFactory = new $adapterFactoryClassName();
        return $adapterFactory;
    }


}
