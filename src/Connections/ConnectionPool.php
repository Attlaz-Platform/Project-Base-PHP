<?php
declare(strict_types=1);

namespace Attlaz\Project\Connections;


use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionFactory;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionInstance;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionPool;
use Attlaz\Adapter\Base\Model\Connection\AdapterRegistrar;
use Attlaz\Client;
use Attlaz\Model\AdapterConnection;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;

class ConnectionPool implements AdapterConnectionPool
{


    /** @var AdapterConnection[]|null */
    private array|null $connectionDefinitions = null;

    public function __construct(private readonly Config $config, private readonly Environment $environment, private readonly Client $client)
    {

    }

    /**
     * @return AdapterConnection[]
     */
    private function getConnectionDefinitions(): array
    {
        if (\is_null($this->connectionDefinitions)) {
            $this->loadConnectionDefinitions();
        }
        return $this->connectionDefinitions;
    }

    private function patchAdapterConnectionConfigurationValues(AdapterConnection $adapterConnection): AdapterConnection
    {
        $keys = $adapterConnection->getConfiguratedKeys();

        foreach ($keys as $key) {
            $value = $adapterConnection->getConfiguration($key);
            if (!\is_null($value)) {
                $value = $this->config->patchConfigValue($value);
                $adapterConnection->setConfiguration($key, $value);
            }
        }
        return $adapterConnection;
    }

    public function getConnection(string $key): ?AdapterConnectionInstance
    {

        $connectionDefinition = $this->getConnectionDefinition($key);
        if (\is_null($connectionDefinition)) {
            // TODO: only show this additional information when in local mode
            $connectionDefinitions = $this->getConnectionDefinitions();
            $available = [];
            foreach ($connectionDefinitions as $connectionDefinition) {
                $available[] = $connectionDefinition->getName() . ' (' . $connectionDefinition->getKey() . ')';

            }
            throw new \Error('No connection found with id "' . $key . '", available: ' . \implode(', ', $available));
        }

        $adapterId = $connectionDefinition->getAdapterId();

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterId);


        if (\is_null($adapterFactoryClassName)) {
            $availableAdapterIds = AdapterRegistrar::getAdapterIds();
            throw new \Exception('Unknown connection adapter "' . $adapterId . '" (available: ' . \implode(', ', $availableAdapterIds) . ') make sure the package is installed');
        }

        /** @var AdapterConnectionFactory $adapterFactory */
        $adapterFactory = new $adapterFactoryClassName($this);


        /** @var AdapterConnectionInstance|null $adapterConnection */
        $adapterConnection = $adapterFactory->createAdapterConnection($connectionDefinition);

        if (\is_null($adapterConnection)) {

        }

        return $adapterConnection;

    }

    private function loadConnectionDefinitions(): void
    {
        $this->connectionDefinitions = $this->client->getConnectionEndpoint()->getConnections($this->environment->getProject()->id);
    }

    public function getConnectionDefinition(string $connectionKey): ?AdapterConnection
    {

        $connectionDefinition = $this->client->getConnectionEndpoint()->getConnection($connectionKey);
        if (!\is_null($connectionDefinition)) {
            $connectionDefinition = $this->patchAdapterConnectionConfigurationValues($connectionDefinition);
        }
        return $connectionDefinition;
    }

    public function getConnectionDefinitionKeys(): array
    {
        //TODO: rewrite with yield
        $result = [];
        foreach ($this->getConnectionDefinitions() as $connectionDefinition) {
            $result[] = $connectionDefinition->getKey();
        }
        return $result;
    }

    public function getDefinedConnections(): array
    {
        //TODO: rewrite with yield
        $result = [];
        foreach ($this->getConnectionDefinitions() as $connectionDefinition) {
            $result[] = [
                'key' => $connectionDefinition->getKey(),
                'type' => $connectionDefinition->getAdapterId()
            ];
        }
        return $result;
    }
}
