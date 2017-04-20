<?php
declare(strict_types=1);

namespace Attlaz\Framework\App;

use Psr\Container\ContainerInterface;

class Project
{
    private $projectName;
    private $container;
    private $commands;

    public function __construct(string $projectName, ContainerInterface $container)
    {

        $this->projectName = $projectName;
        $this->container = $container;
        $this->commands = [];
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function registerCommand(string $commandName, string $commandClass)
    {
        if (isset($this->commands[$commandName])) {
            throw new \Exception('Command "' . $commandName . '" already defined');
        }
        $this->commands[$commandName] = $commandClass;
    }

    public function getCommandNames(): array
    {
        return \array_keys($this->commands);
    }

    public function hasCommand(string $commandName): bool
    {
        return isset($this->commands[$commandName]);
    }

    public function getCommandClass(string $commandName): string
    {
        if (!isset($this->commands[$commandName])) {
            throw new \Exception('Command "' . $commandName . '" not defined');
        }

        return $this->commands[$commandName];
    }
}