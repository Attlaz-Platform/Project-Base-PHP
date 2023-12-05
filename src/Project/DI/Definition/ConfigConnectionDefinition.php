<?php

declare(strict_types=1);

namespace Attlaz\Project\DI\Definition;

use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionInstance;
use Attlaz\ConnectionPool\ConnectionPool;
use Attlaz\ConnectionPool\Model\Error\ConnectionNotFoundError;
use Attlaz\Project\App\Config;
use DI\Definition\Definition;
use DI\Definition\SelfResolvingDefinition;
use Psr\Container\ContainerInterface;

/**
 * Use this when the connection definition is defined in configuration. Pass along the configuration key containing the connection id
 */
class ConfigConnectionDefinition implements Definition, SelfResolvingDefinition
{
    private string $name = '';

    public function __construct(private readonly string $configKey, private readonly string $connectionClass = AdapterConnectionInstance::class)
    {


    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }


    public function resolve(ContainerInterface $container): mixed
    {
        /** @var Config $config */
        $config = $container->get(Config::class);

        $connectionIdentifier = $config->get($this->configKey, 'string');
        if ($connectionIdentifier === null) {
            throw new \Error('Configuration `' . $this->configKey . '` not found');
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = $container->get(ConnectionPool::class);

        try {
            $connection = $connectionPool->getConnection($connectionIdentifier, $this->connectionClass);
        } catch (ConnectionNotFoundError $ex) {
            throw new \Error('Unable to resolve config connection: connection `' . $connectionIdentifier . '` not found for config `' . $this->configKey . '`');
        }


        return $connection;

    }

    public function isResolvable(ContainerInterface $container): bool
    {
        /** @var Config $config */
        $config = $container->get(Config::class);

        $connectionIdentifier = $config->get($this->configKey, 'string');
        if ($connectionIdentifier === null) {
            return false;
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = $container->get(ConnectionPool::class);

        $keys = $connectionPool->getConnectionDefinitionKeys();
        return \array_key_exists($connectionIdentifier, $keys);
    }

    public function replaceNestedDefinitions(callable $replacer): void
    {
        // no nested definitions
    }

    public function __toString(): string
    {
        return 'Config Connection: ' . $this->configKey;
    }
}
