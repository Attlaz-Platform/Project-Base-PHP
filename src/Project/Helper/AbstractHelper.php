<?php

declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Attlaz\Project\App\Config;
use Attlaz\ConnectionPool\ConnectionPool;
use Attlaz\Project\Storage\StorageManager;
use DI\Container;
use Psr\Log\LoggerInterface;

class AbstractHelper
{
    protected LoggerInterface $logger;
    protected Config $config;
    protected StorageManager $storageManager;
    protected Container $dependencyManager;
    protected OutputHelper $outputHelper;
    protected ConnectionPool $connectionPool;
    protected Profiler $profiler;

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
