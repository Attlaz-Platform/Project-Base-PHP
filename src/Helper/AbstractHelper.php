<?php

declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Attlaz\Project\App\Config;
use Attlaz\Project\Cache\CacheManager;
use Attlaz\Project\Connections\ConnectionPool;
use DI\Container;
use Psr\Log\LoggerInterface;

class AbstractHelper
{

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var Container
     */
    protected $dependencyManager;

    /**
     * @var OutputHelper
     */
    protected $outputHelper;

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    public function __construct(HelperContext $context)
    {
        $this->logger = $context->getLogger();
        $this->config = $context->getConfig();
        $this->cacheManager = $context->getCacheManager();
        $this->dependencyManager = $context->getDependencyManager();
        $this->outputHelper = $context->getOutputHelper();
        $this->connectionPool = $context->getConnectionPool();
    }
}
