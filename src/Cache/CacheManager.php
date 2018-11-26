<?php
declare(strict_types=1);

namespace Attlaz\Project\Cache;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Echron\Tools\Normalize\Normalizer;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use MongoDB\Driver\Manager as MongoDBManager;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class CacheManager
{
    private $manager;
    private $logger;
    private $database;

    private $fileCachePath;

    private $pool;

    public const DEFAULT_NAMESPACE = 'default';

    public function __construct(
        MongoDBManager $manager,
        string $database,
        string $fileCachePath,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->database = $database;

        $this->fileCachePath = $fileCachePath;
        $this->logger = $logger;

        $this->pool = [];
    }

    public function getCache(string $name = self::DEFAULT_NAMESPACE): CacheInterface
    {
        $name = Normalizer::normalize($name);

        if (!isset($this->pool[$name])) {
            $this->createCachePool($name);
        }

        return $this->pool[$name];
    }

    private function createCachePool(string $name): void
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
        $collection = new \MongoDB\Collection($this->manager, 'cache_' . $this->database, $name);
        $mongoDBCache = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);
        $mongoDBCache->setLogger($this->logger);
        $failOverCachePool->addCachePool('mongodb', $mongoDBCache);
        /**
         * File
         */
        $filesystemAdapter = new Local($this->fileCachePath);
        $filesystem = new Filesystem($filesystemAdapter);

        $fileCachePool = new FilesystemCachePool($filesystem, $this->database . \DIRECTORY_SEPARATOR . $name);
        $failOverCachePool->addCachePool('file', $fileCachePool);
        /**
         * Memory
         */
        $memoryCache = new \Cache\Adapter\PHPArray\ArrayCachePool(null);
        $failOverCachePool->addCachePool('memory', $memoryCache);

        $this->pool[$name] = $failOverCachePool;
    }
}
