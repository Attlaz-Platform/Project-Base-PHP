<?php
declare(strict_types=1);

namespace Attlaz\Project\Connections;


use Attlaz\Adapter\Base\RemoteService\SSH2RemoteService;
use Attlaz\Client;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Psr\Log\LoggerInterface;

class ConnectionPool
{
    private Config $config;
    private Environment $environment;
    private Client $client;
    private LoggerInterface $logger;

    /** @var AdapterConnectionDefinition[]|null */
    private ?array $connectionDefinitions = null;

    public function __construct(Config $config, Environment $environment, Client $client, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->environment = $environment;
        $this->client = $client;
        $this->logger = $logger;

    }

    private function createSSHConnection(AdapterConnectionDefinition $connectionDefinition): SSH2RemoteService
    {

        // TODO: move this to the connection module itself
        $hostname = $connectionDefinition->getConfiguration('hostname');
        if (!\is_null($hostname)) {
            $hostname = $this->config->patchConfigValue($hostname);
        }

        $port = (int)$connectionDefinition->getConfiguration('port');
//        $port = $this->config->patchConfigValue($port);

        $username = $connectionDefinition->getConfiguration('username');
//        $username = $this->config->patchConfigValue($username);

        $key = $connectionDefinition->getConfiguration('key');
        if (!\is_null($key)) {
            $key = $this->config->patchConfigValue($key);
        }


        $password = $connectionDefinition->getConfiguration('password');
        if (!\is_null($password)) {
            $password = $this->config->patchConfigValue($password);
        }

        $ssh = new SSH2RemoteService($hostname, $port);

        if (!empty($key)) {
            $ssh->authenticateWithKeyFile($username, $key);
        } else {
            $ssh->authenticate($username, $password);
        }

        return $ssh;
    }

//    private function getConfiguration(array $connectionDefinition, string $key): ?string
//    {
//        $configurations = $connectionDefinition['configuration'];
//        foreach ($configurations as $configuration) {
//            if ($configuration['key'] === $key) {
//                $value = $configuration['value'];
//                if (\is_string($value)) {
//                    $value = $this->config->patchConfigValue($value);
//                }
//                return $value;
//            }
//        }
//        echo $key . ' not found' . \PHP_EOL;
//        return null;
//    }


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


        switch ($adapterName) {
            case 'ssh':
                return $this->createSSHConnection($connectionDefinition);
                break;
        }

        $adapterFactoryClassName = AdapterRegistrar::getFactoryClassName($adapterName);


        if (\is_null($adapterFactoryClassName)) {
            throw new \Exception('Unknown connection adapter ' . $adapterName . ' make sure the package is installed');
        }

        /** @var AdapterFactory $adapterFactory */
        $adapterFactory = new $adapterFactoryClassName($this);
        $adapterConnection = $adapterFactory->createAdapterConnection($connectionDefinition);

        if (\is_null($adapterConnection)) {

        }

        return $adapterConnection;

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

    public function getConnectionDefinitionKeys(): array
    {
        if (\is_null($this->connectionDefinitions)) {
            $this->loadConnectionDefinitions();
        }
        //TODO: rewrite with yield
        $result = [];
        foreach ($this->connectionDefinitions as $connectionDefinition) {
            $result[] = $connectionDefinition->getKey();
        }
        return $result;
    }

    public function getDefinedConnections(): array
    {
        if (\is_null($this->connectionDefinitions)) {
            $this->loadConnectionDefinitions();
        }
        //TODO: rewrite with yield
        $result = [];
        foreach ($this->connectionDefinitions as $connectionDefinition) {
            $result[] = [
                'key'  => $connectionDefinition->getKey(),
                'type' => $connectionDefinition->getAdapterName()
            ];
        }
        return $result;
    }
}
