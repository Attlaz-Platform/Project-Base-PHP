<?php

declare(strict_types=1);

namespace Attlaz\Project\Connections;

use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionDefinition;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionFactory;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionInstance;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionPool;
use Attlaz\Adapter\Base\Model\Connection\AdapterRegistrar;
use Attlaz\Client;
use Attlaz\Model\AdapterConnection;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Psr\Log\LoggerInterface;

class ConnectionPool implements AdapterConnectionPool
{
    /** @var AdapterConnection[]|null */
    private array|null $connectionDefinitions = null;

    public function __construct(
        private readonly Config          $config,
        private readonly Environment     $environment,
        private readonly LoggerInterface $logger,
        private readonly Client          $client
    )
    {

    }

    /**
     * @return AdapterConnection[]
     */
    private function getConnectionDefinitions(): array
    {
        if ($this->connectionDefinitions === null) {
            $this->loadConnectionDefinitions();
        }
        return $this->connectionDefinitions;
    }

    private function patchAdapterConnectionConfigurationValues(AdapterConnection $adapterConnection): AdapterConnectionDefinition
    {

        $rawData = [
            'id' => $adapterConnection->getId(),
            'key' => $adapterConnection->getKey(),
            'name' => $adapterConnection->getName(),
            'adapter' => $adapterConnection->getAdapterId(),
        ];
        $result = new AdapterConnectionDefinition($rawData);

        $configurations = $this->client->getConnectionEndpoint()->getAdapterConfiguration($adapterConnection->getAdapterId());

        $configValues = $this->client->getConnectionEndpoint()->getConnectionConfiguration($adapterConnection->getId());

        foreach ($configurations as $configuration) {
            $value = null;
            foreach ($configValues as $configValue) {
                if ($configValue->getConfigId() === $configuration->getId()) {
                    $value = $configValue->getValue();
                }
            }
            if ($value !== null) {
                $value = $this->config->patchConfigValue($value);

                $result->setConfiguration($configuration->getKey(), $value);
            }

        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getConnection(string $connectionId, string $className): AdapterConnectionInstance|null
    {
        $connectionDefinition = $this->getConnectionDefinition($connectionId);
        if ($connectionDefinition === null) {
            return null;
        }

        $adapterId = $connectionDefinition->getAdapterId();

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterId);


        if ($adapterFactoryClassName === null) {
            $availableAdapterIds = AdapterRegistrar::getAdapterIds();
            throw new \Exception('Unknown connection adapter "' . $adapterId . '" (available: ' . \implode(', ', $availableAdapterIds) . ') make sure the package is installed');
        }

        /** @var AdapterConnectionFactory $adapterFactory */
        $adapterFactory = new $adapterFactoryClassName($this);


        $adapterConnection = $adapterFactory->createAdapterConnection($connectionDefinition);

        if ($adapterConnection !== null) {
            if (get_class($adapterConnection) !== $className) {
                $this->logger->warning('Adapter connection should be `' . $className . '`, got `' . get_class($adapterConnection) . '` instead');
            }
        }

        return $adapterConnection;

    }

    private function loadConnectionDefinitions(): void
    {
        $this->connectionDefinitions = $this->client->getConnectionEndpoint()->getConnections($this->environment->getProject()->id);
    }

    public function getConnectionDefinition(string $connectionId): AdapterConnectionDefinition|null
    {
        $connectionDefinition = $this->client->getConnectionEndpoint()->getConnection($connectionId);
        if ($connectionDefinition === null) {
            return null;
        }
        return $this->patchAdapterConnectionConfigurationValues($connectionDefinition);

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
                'type' => $connectionDefinition->getAdapterId(),
            ];
        }
        return $result;
    }
}
