<?php

declare(strict_types=1);

namespace Attlaz\Project\DI\Definition;

use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionInstance;
use Attlaz\Project\App\Config;
use Attlaz\ConnectionPool\ConnectionPool;
use DI\Definition\Definition;
use DI\Definition\SelfResolvingDefinition;
use Psr\Container\ContainerInterface;

/**
 * Use this when the connection definition is defined in configuration. Pass along the configuration key containing the connection id
 */
class ConfigConnectionDefinition implements Definition, SelfResolvingDefinition
{
    private string $name = '';

    private string $configKey;

    public function __construct(string $configKey)
    {
        $this->configKey = $configKey;

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

        /** @var ConnectionPool $connectionPool */
        $connectionPool = $container->get(ConnectionPool::class);

        return $connectionPool->getConnection($connectionIdentifier, AdapterConnectionInstance::class);

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
