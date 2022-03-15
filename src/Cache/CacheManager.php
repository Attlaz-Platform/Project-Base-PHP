<?php

declare(strict_types=1);

namespace Attlaz\Project\Cache;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Storage\StorageCachePool;
use Attlaz\Project\Storage\StorageEngine;
use Echron\Tools\Normalize\Normalizer;
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

    public const KEY_SYSTEM = '_sys';
    /** @var Client */
    private $attlazClient;

    /** @var StorageEngine */
    private $storageEngine;

    public function __construct(
        Environment     $environment,
        Client          $attlazClient,
        LoggerInterface $logger
    )
    {
        $this->environment = $environment;
        $this->attlazClient = $attlazClient;
        $this->logger = $logger;

        $this->fileCachePath = $environment->getFileCachePath();

        $this->pools = [];

        $this->storageEngine = new StorageEngine($this->attlazClient, $this->environment, 'cache');


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
     * @param bool $inclExternal
     * @param bool $inclSys
     * @return string[]
     */
    public function getCachePoolKeys(bool $inclExternal = false, bool $inclSys = false): array
    {
        if ($inclExternal) {
            $cachePoolKeys = $this->storageEngine->getPoolKeys();
            if ($inclSys) {
                $cachePoolKeys[] = self::KEY_SYSTEM;
            }

            return $cachePoolKeys;
        }

        return \array_keys($this->pools);
    }

    public function cleanCachePool(string $key): bool
    {
        $cachePool = $this->createCachePool($key);

        return $cachePool->clear();
    }


    private function createCachePool(string $key): FailOverCachePool
    {
        //TODO: is it possible to instantiate this trought the DI?
        $failOverCachePool = new FailOverCachePool([
            'skip_on_failure'        => true,
            'remove_pool_on_failure' => true,
        ]);

        $failOverCachePool->setLogger($this->logger);

        $storageCachePool = new StorageCachePool($this->storageEngine);
        $failOverCachePool->addCachePool('storage', $storageCachePool);


//        if ($key !== self::KEY_SYSTEM) {
//            $this->addExternalCachePoolKeys($key);
//        }

        return $failOverCachePool;
    }

//    private function getExternalCachePoolKeys(): array
//    {
//        $sysCachePool = $this->createCachePool(self::KEY_SYSTEM);
//
//        $cachePools = $sysCachePool->get('cache_pools');
//        if (\is_null($cachePools) || !\is_array($cachePools)) {
//            $cachePools = [];
//        }
//
//        return $cachePools;
//    }

//    private function addExternalCachePoolKeys(string $key): array
//    {
//        $sysCachePool = $this->createCachePool(self::KEY_SYSTEM);
//
//        $cachePools = $this->getExternalCachePoolKeys();
//
//        if (!\in_array($key, $cachePools)) {
//            $cachePools[] = $key;
//
//            $sysCachePool->set('cache_pools', $cachePools);
//        }
//
//        return $cachePools;
//    }
}
