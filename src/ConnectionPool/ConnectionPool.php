<?php

declare(strict_types=1);

namespace Attlaz\ConnectionPool;

use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionDefinition;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionFactory;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionInstance;
use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionPool;
use Attlaz\Adapter\Base\Model\Connection\AdapterRegistrar;
use Attlaz\Client;
use Attlaz\ConnectionPool\Model\Error\ConnectionNotFoundError;
use Attlaz\Model\AdapterConnection;
use Attlaz\Model\AdapterConnectionConfigurationValue;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use DI\Container;
use Psr\Log\LoggerInterface;

class ConnectionPool implements AdapterConnectionPool
{
    /** @var AdapterConnection[]|null */
    private array|null $connectionDefinitions = null;

    public function __construct(
        private readonly Config          $config,
        private readonly Environment     $environment,
        private readonly LoggerInterface $logger,
        private readonly Container       $objectManager,
        private readonly Client          $client
    )
    {

    }

    /**
     * @inheritDoc
     */
    public function getConnection(string $connectionId, string $className): AdapterConnectionInstance|null
    {
        $connectionDefinition = $this->getConnectionDefinition($connectionId);
        if ($connectionDefinition === null) {
            throw new ConnectionNotFoundError('No connection definition found for `' . $connectionId . '`');
        }

        $adapterId = $connectionDefinition->getAdapterId();

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterId);


        if ($adapterFactoryClassName === null) {
            $availableAdapterIds = AdapterRegistrar::getAdapterIds();
            throw new \Exception('Unknown connection adapter "' . $adapterId . '" (available: ' . \implode(', ', $availableAdapterIds) . ') make sure the package is installed');
        }


        $adapterFactory = $this->objectManager->get($adapterFactoryClassName);
        if (!$adapterFactory instanceof AdapterConnectionFactory) {
            throw new \RuntimeException('Adapter factory `' . $adapterFactoryClassName . '` must implements `' . AdapterConnectionFactory::class . '`');
        }


        $adapterConnection = $adapterFactory->createAdapterConnection($connectionDefinition);

        if ($adapterConnection !== null) {
            if (!is_a($adapterConnection, $className)) {
                $this->logger->warning('Adapter connection should be `' . $className . '`, got `' . get_class($adapterConnection) . '` instead');
            }
        }

        try {
            // TODO: add data to event (which flow, flow-run, etc)
            $this->client->getConnectionEndpoint()->createConnectionEvent($connectionDefinition->getId(), 'used');
        } catch (\Throwable $ex) {
            $this->logger->warning('Unable to mark connection as used', ['error' => $ex]);
        }

        $this->logger->info('Use connection `' . $connectionDefinition->getName() . '`');
        return $adapterConnection;

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
            $value = $this->getValue($configValues, $configuration->getId());

            if ($value !== null) {
                if (is_string($value)) {
                    $value = $this->config->patchConfigValue($value);
                    $result->setConfiguration($configuration->getKey(), $value);
                } else {
                    if (str_starts_with($configuration->getType(), 'oauth:')) {
                        // Parse oauth information
                        $value = $value['access_token'];
                        $result->setConfiguration($configuration->getKey(), $value);
                    } else {
                        throw new \Exception('Invalid configuration');
                    }
                }


            }

        }

        return $result;
    }

    /**
     * @param AdapterConnectionConfigurationValue[] $configValues
     * @param $configurationId
     * @return null
     */
    private function getValue(array $configValues, string $configurationId): mixed
    {
        foreach ($configValues as $configValue) {
            if ($configValue->getAdapterConfigurationId() === $configurationId) {
                return $configValue->getValue();
            }
        }
        return null;
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

    private function loadConnectionDefinitions(): void
    {
        $this->connectionDefinitions = $this->client->getConnectionEndpoint()->getConnections($this->environment->getProject()->id);
    }
}
