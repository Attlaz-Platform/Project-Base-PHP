<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

use Attlaz\ConnectionPool\ConnectionPool;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Helper\OutputHelper;
use Attlaz\Project\Helper\Profiler;
use Attlaz\Project\Storage\StorageManager;
use DI\Container;
use Psr\Log\LoggerInterface;

class Context
{
    public function __construct(
        protected LoggerInterface $logger,
        protected Environment     $environment,
        protected Config          $config,
        protected StorageManager  $storageManager,
        protected Container       $dependencyManager,
        protected OutputHelper    $outputHelper,
        protected ConnectionPool  $connectionPool,
        protected Profiler        $profiler
    )
    {
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

    public function getProfiler(): Profiler
    {
        return $this->profiler;
    }
}
