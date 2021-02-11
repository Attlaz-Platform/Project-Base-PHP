<?php


namespace Attlaz\Project\Connections;


use Attlaz\Adapter\Base\RemoteService\SSH2RemoteService;
use Attlaz\Client;
use Attlaz\Project\App\Config;
use Attlaz\Project\Logger\Logger;

class ConnectionPool
{
    private $config;
    private $client;
    private $logger;

    private $connections = [];

    public function __construct(Config $config, Client $client, Logger $logger)
    {
        $this->config = $config;
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
            throw new \Error('No connection found with id "' . $key . '"');
        }

        switch ($connectionDefinition['type']) {
            case 'ssh':

                return $this->createSSHConnection($connectionDefinition);


                break;
            default:
                throw new \Exception('Unknown connection type ' . $connectionDefinition['type']);
        }

        return null;
    }

    private function getConnectionDefinition(string $key): ?array
    {
        if (\is_null($this->connections)) {
            $this->connections = $client->getConnections($environment->getProject()->id);
        }
        foreach ($this->connections as $connectionDefinition) {
            if ($connectionDefinition['key'] === $key) {
                return $connectionDefinition;
            }
        }
        return null;
    }
}
