<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Cache\CacheManager;
use Attlaz\Project\Helper\OutputHelper;
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
    protected $environment;

    /**
     * @var Config
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

    /**
     * @var OutputHelper
     */
    protected $outputHelper;

    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        Config $config,
        CacheManager $cacheManager,
        Container $dependencyManager,
        OutputHelper $outputHelper
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->config = $config;
        $this->cacheManager = $cacheManager;
        $this->dependencyManager = $dependencyManager;
        $this->outputHelper = $outputHelper;
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

    public function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }

    public function getDependencyManager(): Container
    {
        return $this->dependencyManager;
    }

    public function getOutputHelper(): OutputHelper
    {
        return $this->outputHelper;
    }
}
