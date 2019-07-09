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

    public const KEY_SYSTEM = '_sys';

    public function __construct(
        Environment $environment,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->logger = $logger;

        $this->fileCachePath = $environment->getFileCachePath();

        $this->pools = [];

        $this->getExternalCachePoolKeys();
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
            $cachePoolKeys = $this->getExternalCachePoolKeys();
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

        if ($key !== self::KEY_SYSTEM) {
            $this->addExternalCachePoolKeys($key);
        }

        return $failOverCachePool;
    }

    private function getExternalCachePoolKeys(): array
    {
        $sysCachePool = $this->createCachePool(self::KEY_SYSTEM);

        $cachePools = $sysCachePool->get('cache_pools');
        if (\is_null($cachePools) || !\is_array($cachePools)) {
            $cachePools = [];
        }

        return $cachePools;
    }

    private function addExternalCachePoolKeys(string $key): array
    {
        $sysCachePool = $this->createCachePool(self::KEY_SYSTEM);

        $cachePools = $this->getExternalCachePoolKeys();

        if (!\in_array($key, $cachePools)) {
            $cachePools[] = $key;

            $sysCachePool->set('cache_pools', $cachePools);
        }

        return $cachePools;
    }
}
