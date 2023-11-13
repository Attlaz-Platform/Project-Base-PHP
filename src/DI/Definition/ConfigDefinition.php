<?php

declare(strict_types=1);

namespace Attlaz\Project\DI\Definition;

use Attlaz\Project\App\Config;
use DI\Definition\Definition;
use DI\Definition\SelfResolvingDefinition;
use Psr\Container\ContainerInterface;

class ConfigDefinition implements Definition, SelfResolvingDefinition
{
    /**
     * Entry name.
     * @var string
     */
    private string $name = '';

    private string $key;
    private string|null $datatype;

    public function __construct(string $key, string $datatype = null)
    {
        $this->key = $key;
        $this->datatype = $datatype;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function resolve(ContainerInterface $container): mixed
    {
        return self::resolveExpression($this->name, $this->key, $container, $this->datatype);
    }

    public function isResolvable(ContainerInterface $container): bool
    {
        /** @var Config $config */
        $config = $container->get(Config::class);

        return $config->has($this->key);
    }

    public function replaceNestedDefinitions(callable $replacer): void
    {
        // no nested definitions
    }

    public function __toString(): string
    {
        return 'Config: ' . $this->key;
    }

    /**
     * Resolve a string expression.
     */
    public static function resolveExpression(
        string             $entryName,
        string             $key,
        ContainerInterface $container,
        string             $datatype = null
    )
    {
        /** @var Config $config */
        $config = $container->get(Config::class);

        return $config->get($key, $datatype);
    }
}
