<?php

declare(strict_types=1);

namespace Attlaz\Project\DI\Definition;

use Attlaz\Project\App\Config;
use Attlaz\Project\Connections\ConnectionPool;
use DI\Definition\Definition;
use DI\Definition\SelfResolvingDefinition;
use Psr\Container\ContainerInterface;

class ConnectionDefinition implements Definition, SelfResolvingDefinition
{
    /**
     * Entry name.
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $connectionIdentifier;


    public function __construct(string $connectionIdentifier)
    {
        $this->connectionIdentifier = $connectionIdentifier;

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getConnectionIdentifier(): string
    {
        return $this->connectionIdentifier;
    }

    public function resolve(ContainerInterface $container): mixed
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = $container->get(ConnectionPool::class);

        return $connectionPool->getConnection($this->connectionIdentifier);

    }

    public function isResolvable(ContainerInterface $container): bool
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = $container->get(ConnectionPool::class);

        $keys = $connectionPool->getConnectionDefinitionKeys();
        return \array_key_exists($this->connectionIdentifier, $keys);
    }

    public function replaceNestedDefinitions(callable $replacer): void
    {
        // no nested definitions
    }

    public function __toString(): string
    {
        return 'Connection: ' . $this->connectionIdentifier;
    }
}
