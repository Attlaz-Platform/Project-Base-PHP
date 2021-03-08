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

    private $connections;

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
            foreach ($this->connections as $connectionDefinition) {
                $available[] = $connectionDefinition['key'];
            }
            throw new \Error('No connection found with id "' . $key . '", available: ' . \implode(', ', $available));
        }
        switch ($connectionDefinition['adapter']) {
            case 'ssh':

                return $this->createSSHConnection($connectionDefinition);


                break;
            default:
                throw new \Exception('Unknown connection adapter ' . $connectionDefinition['adapter']);
        }

        return null;
    }

    private function getConnectionDefinition(string $key): ?array
    {
        if (\is_null($this->connections)) {
            $this->connections = $this->client->getConnections($this->environment->getProject()->id);
        }
        foreach ($this->connections as $connectionDefinition) {
            if ($connectionDefinition['key'] === $key) {
                return $connectionDefinition;
            }
        }
        return null;
    }
}
