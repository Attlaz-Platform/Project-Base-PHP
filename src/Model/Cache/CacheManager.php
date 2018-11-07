<?php
declare(strict_types=1);

namespace Attlaz\Project\Model\Cache;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class CacheManager
{
    private $manager;
    private $logger;
    private $database;

    private $pool;

    public function __construct(\MongoDB\Driver\Manager $manager, string $database, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
        $this->database = $database;

        $this->pool = [];
    }

    public function getCache(string $name = 'default'): CacheInterface
    {
        if (!isset($this->pool[$name])) {
            $collection = new \MongoDB\Collection($this->manager, $this->database,  $name);

            $cachePools = [];

            $mongoDBCache = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);
            $mongoDBCache->setLogger($this->logger);

            $cachePools[] = $mongoDBCache;

            $fileCache = new \Cache\Adapter\PHPArray\ArrayCachePool(null);
            $cachePools[] = $fileCache;

            $cache = new FailOverCachePool($cachePools, [
                'skip_on_failure'        => true,
                'remove_pool_on_failure' => true,
            ]);
            $cache->setLogger($this->logger);

            $this->pool[$name] = $cache;
        }

        return $this->pool[$name];
    }
}