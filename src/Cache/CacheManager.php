<?php
declare(strict_types=1);

namespace Attlaz\Project\Cache;

use Attlaz\Project\App\Environment;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Adapter\MongoDB\MongoDBCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Echron\Tools\Normalize\Normalizer;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use MongoDB\Collection;
use MongoDB\Driver\Manager;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class CacheManager
{

    private $environment;
    protected $logger;

    private $fileCachePath;

    /** @var FailOverCachePool[] */
    private $pools;

    public const DEFAULT_NAMESPACE = 'default';

    public function __construct(
        Environment $environment,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->logger = $logger;

        $this->fileCachePath = $environment->getFileCachePath();

        $this->pools = [];
    }

    public function getCache(string $key = self::DEFAULT_NAMESPACE): CacheInterface
    {
        $key = Normalizer::normalize($key);

        if (!isset($this->pools[$key])) {
            $this->pools[$key] = $this->createCachePool($key);
        }

        return $this->pools[$key];
    }

    /**
     * @return string[]
     */
    public function getCachePoolKeys(): array
    {
        return \array_keys($this->pools);
    }

    private function createCachePool(string $key): FailOverCachePool
    {
        //TODO: is it possible to instantiate this trought the DI?
        $failOverCachePool = new FailOverCachePool([
            'skip_on_failure'        => true,
            'remove_pool_on_failure' => true,
        ]);

        $failOverCachePool->setLogger($this->logger);

        /**
         * MongoDB
         */
        if (extension_loaded("mongodb")) {
            $mongoDBManager = new Manager($this->environment->mongoDBConnectionString, ['readPreference' => 'nearest']);
            $collection = new Collection($mongoDBManager, 'cache_' . $this->environment->getCacheName(), $key);
            $mongoDBCache = new MongoDBCachePool($collection);

            $mongoDBCache->setLogger($this->logger);

            $failOverCachePool->addCachePool('mongodb', $mongoDBCache);
        }

        /**
         * File
         */
        $filesystemAdapter = new Local($this->fileCachePath);
        $filesystem = new Filesystem($filesystemAdapter);

        $fileCacheLocation = \Echron\Tools\FileSystem::joinPath($this->environment->getCacheName(), $key);
        $fileCachePool = new FilesystemCachePool($filesystem, $fileCacheLocation);
        $failOverCachePool->addCachePool('file', $fileCachePool);
        /**
         * Memory
         */
        $memoryCache = new ArrayCachePool(null);
        $failOverCachePool->addCachePool('memory', $memoryCache);

        return $failOverCachePool;
    }
}
