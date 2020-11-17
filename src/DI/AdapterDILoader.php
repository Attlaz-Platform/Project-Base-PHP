<?php
declare(strict_types=1);


namespace Attlaz\Project\DI;


use Attlaz\Adapter\Base\Model\DefinitionsFactory;
use Attlaz\Project\App\Config;
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
        // TODO: get list of active adapters for this environment

        $activeAdapters = ['Magento2' => '0.0.1'];


        foreach ($activeAdapters as $activeAdapter => $version) {

            $factory = $this->getDIFactory($activeAdapter);
            if (!\is_null($factory)) {
                $magento2Definitions = $factory->getDefinitions($config);
                $containerBuilder->addDefinitions($magento2Definitions);
            }

        }


    }

    private function getDIFactory(string $adapterName): ?DefinitionsFactory
    {

        $className = 'Attlaz\Adapter\\' . $adapterName . '\DI';
        if (!\class_exists($className)) {
            $this->logger->warning('Unable to load "' . $adapterName . '" adapter, class "' . $className . '" does not exist');
            return null;
        }
        return new $className();
    }


}
