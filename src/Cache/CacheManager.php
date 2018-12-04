<?php
declare(strict_types=1);

namespace Attlaz\Project\Cache;

use Attlaz\Project\App\Environment;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Echron\Tools\Normalize\Normalizer;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class CacheManager
{

    private $logger;

    private $environment;

    private $fileCachePath;

    private $pool;

    public const DEFAULT_NAMESPACE = 'default';

    public function __construct(
        Environment $environment,
        LoggerInterface $logger
    ) {
        $this->fileCachePath = $environment->getFileCachePath();

        $this->environment = $environment;
        $this->logger = $logger;

        $this->pool = [];
    }

    public function getCache(string $name = self::DEFAULT_NAMESPACE): CacheInterface
    {
        $name = Normalizer::normalize($name);

        if (!isset($this->pool[$name])) {
            $this->pool[$name] = $this->createCachePool($name);
        }

        return $this->pool[$name];
    }

    public function getCachePools(): array
    {
        return \array_keys($this->pool);
    }

    private function createCachePool(string $name): FailOverCachePool
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
            $mongoDBManager = new \MongoDB\Driver\Manager($this->environment->mongoDBConnectionString, ['readPreference' => 'nearest']);
            $collection = new \MongoDB\Collection($mongoDBManager, 'cache_' . $this->environment->getCacheName(), $name);
            $mongoDBCache = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);
            $mongoDBCache->setLogger($this->logger);
            $failOverCachePool->addCachePool('mongodb', $mongoDBCache);
        }

        /**
         * File
         */
        $filesystemAdapter = new Local($this->fileCachePath);
        $filesystem = new Filesystem($filesystemAdapter);

        $fileCachePool = new FilesystemCachePool($filesystem, $this->environment->getCacheName() . \DIRECTORY_SEPARATOR . $name);
        $failOverCachePool->addCachePool('file', $fileCachePool);
        /**
         * Memory
         */
        $memoryCache = new \Cache\Adapter\PHPArray\ArrayCachePool(null);
        $failOverCachePool->addCachePool('memory', $memoryCache);

        return $failOverCachePool;
    }
}
