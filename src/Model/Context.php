<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use Attlaz\Project\App\Environment;
use Attlaz\Project\Cache\CacheManager;
use DI\Container;
use Psr\Log\LoggerInterface;

class Context
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var \Attlaz\Project\App\Environment
     */
    protected $config;
    /**
     * @var \Attlaz\Project\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var \DI\Container
     */
    protected $dependencyManager;

    public function __construct(
        LoggerInterface $logger,
        Environment $config,
        CacheManager $cacheManager,
        Container $dependencyManager
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->cacheManager = $cacheManager;
        $this->dependencyManager = $dependencyManager;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getConfig(): Environment
    {
        return $this->config;
    }

    public function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }

    public function getDependencyManager(): Container
    {
        return $this->dependencyManager;
    }
}
