<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Connections\ConnectionPool;
use Attlaz\Project\Helper\OutputHelper;
use Attlaz\Project\Storage\StorageManager;
use DI\Container;
use Psr\Log\LoggerInterface;

class Context
{
    protected LoggerInterface $logger;
    protected \Attlaz\Project\App\Environment $environment;
    protected Config $config;
    protected StorageManager $storageManager;
    protected \DI\Container $dependencyManager;
    protected OutputHelper $outputHelper;
    protected ConnectionPool $connectionPool;

    public function __construct(
        LoggerInterface $logger,
        Environment     $environment,
        Config          $config,
        StorageManager  $storageManager,
        Container       $dependencyManager,
        OutputHelper    $outputHelper,
        ConnectionPool  $connectionPool
    )
    {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->config = $config;
        $this->storageManager = $storageManager;
        $this->dependencyManager = $dependencyManager;
        $this->outputHelper = $outputHelper;
        $this->connectionPool = $connectionPool;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getStorageManager(): StorageManager
    {
        return $this->storageManager;
    }

    public function getDependencyManager(): Container
    {
        return $this->dependencyManager;
    }

    public function getOutputHelper(): OutputHelper
    {
        return $this->outputHelper;
    }

    public function getConnectionPool(): ConnectionPool
    {
        return $this->connectionPool;
    }
}
