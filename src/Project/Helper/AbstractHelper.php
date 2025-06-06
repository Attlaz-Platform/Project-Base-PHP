<?php

declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Attlaz\ConnectionPool\ConnectionPool;
use Attlaz\Project\App\Config;
use Attlaz\Project\Storage\StorageManager;
use DI\Container;
use Psr\Log\LoggerInterface;

class AbstractHelper
{
    protected readonly LoggerInterface $logger;
    protected readonly Config $config;
    protected readonly StorageManager $storageManager;
    protected readonly Container $dependencyManager;
    protected readonly OutputHelper $outputHelper;
    protected readonly ConnectionPool $connectionPool;
    protected readonly Profiler $profiler;

    public function __construct(HelperContext $context)
    {
        $this->logger = $context->getLogger();
        $this->config = $context->getConfig();
        $this->storageManager = $context->getStorageManager();
        $this->dependencyManager = $context->getDependencyManager();
        $this->outputHelper = $context->getOutputHelper();
        $this->connectionPool = $context->getConnectionPool();
        $this->profiler = $context->getProfiler();
    }
}
