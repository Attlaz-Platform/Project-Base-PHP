<?php
declare(strict_types=1);

namespace Attlaz\Project\Helper;

class AbstractHelper
{

    /**
     * @var \Psr\Log\LoggerInterface
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

    public function __construct(HelperContext $context)
    {
        $this->logger = $context->getLogger();
        $this->config = $context->getConfig();
        $this->cacheManager = $context->getCacheManager();
        $this->dependencyManager = $context->getDependencyManager();
    }
}
