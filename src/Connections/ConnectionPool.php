<?php


namespace Attlaz\Project\Connections;


use Attlaz\Adapter\Base\RemoteService\SSH2RemoteService;
use Attlaz\Client;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Psr\Log\LoggerInterface;

class ConnectionPool
{
    private $config;
    private $environment;
    private $client;
    private $logger;

    /** @var AdapterConnectionDefinition[] */
    private $connectionDefinitions;

    public function __construct(Config $config, Environment $environment, Client $client, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->environment = $environment;
        $this->client = $client;
        $this->logger = $logger;

    }

    private function createSSHConnection(array $connectionDefinition)
    {

        // TODO: move this to the connection module itself
        $hostname = $this->getConfiguration($connectionDefinition, 'hostname');
        $port = $this->getConfiguration($connectionDefinition, 'port');
        $username = $this->getConfiguration($connectionDefinition, 'username');
        $key = $this->getConfiguration($connectionDefinition, 'key');
        $password = $this->getConfiguration($connectionDefinition, 'password');

        $ssh = new SSH2RemoteService($hostname, $port);

        if (!empty($key)) {
            $ssh->authenticateWithKeyFile($username, $key);
        } else {
            $ssh->authenticate($username, $password);
        }

        return $ssh;
    }

    private function getConfiguration(array $connectionDefinition, string $key): ?string
    {
        $configurations = $connectionDefinition['configuration'];
        foreach ($configurations as $configuration) {
            if ($configuration['key'] === $key) {
                $value = $configuration['value'];
                if (\is_string($value)) {
                    $value = $this->config->patchConfigValue($value);
                }
                return $value;
            }
        }
        echo $key . ' not found' . \PHP_EOL;
        return null;
    }


    public function getConnection(string $key)
    {

        $connectionDefinition = $this->getConnectionDefinition($key);
        if (\is_null($connectionDefinition)) {
            $available = [];
            foreach ($this->connectionDefinitions as $connectionDefinition) {
                $available[] = $connectionDefinition->getKey();
            }
            throw new \Error('No connection found with id "' . $key . '", available: ' . \implode(', ', $available));
        }

        $adapterName = $connectionDefinition->getAdapterName();

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterName);


        if (\is_null($adapterFactoryClassName)) {
            throw new \Exception('Unknown connection adapter ' . $adapterName . ' make sure the package is installed');
        }

        /** @var AdapterFactory $adapterFactory */
        $adapterFactory = new $adapterFactoryClassName();
        $adapter = $adapterFactory->createAdapterConnection($connectionDefinition);
        return $adapter;

    }

    private function loadConnectionDefinitions(): void
    {
        $rawConnectionDefinitions = $this->client->getConnections($this->environment->getProject()->id);

        $connectionDefinitions = [];
        foreach ($rawConnectionDefinitions as $rawConnectionDefinition) {
            $connectionDefinitions[] = new AdapterConnectionDefinition($rawConnectionDefinition);
        }
        $this->connectionDefinitions = $connectionDefinitions;
    }

    private function getConnectionDefinition(string $key): ?AdapterConnectionDefinition
    {
        if (\is_null($this->connectionDefinitions)) {
            $this->loadConnectionDefinitions();
        }
        foreach ($this->connectionDefinitions as $connectionDefinition) {
            if ($connectionDefinition->getKey() === $key) {
                return $connectionDefinition;
            }
        }
        return null;
    }
}
