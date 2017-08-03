<?php
declare(strict_types=1);

namespace Attlaz\Project\Model\Cache;

use Cache\Adapter\Chain\Exception\NoPoolAvailableException;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\Exception\CachePoolException;
use Cache\Adapter\Common\PhpCacheItem;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\SimpleCache\CacheInterface;

class FailOverCachePool extends AbstractCachePool
{
    use LoggerTrait;
    /**
     * @type LoggerInterface
     */
    private $logger;
    /**
     * @type CacheInterface[]
     */
    private $caches;
    /**
     * @type array
     */
    private $options;

    /**
     * @param array $pools
     * @param array $options {
     *
     * @type  bool $skip_on_failure If true we will remove a pool form the chain if it fails.
     * }
     */
    public function __construct(array $caches, array $options = [])
    {
        foreach ($caches as $cache) {
            if (!$cache instanceof AbstractCachePool) {
                throw new \InvalidArgumentException('Cache  must implements AbstractCachePool interface');
            }
        }

        $this->caches = $caches;
        if (!isset($options['skip_on_failure'])) {
            $options['skip_on_failure'] = false;
        }
        if (!isset($options['remove_pool_on_failure'])) {
            $options['remove_pool_on_failure'] = false;
        }
        $this->options = $options;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logs with an arbitrary level if the logger exists.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    protected function log($level, $message, array $context = [])
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * @return AbstractCachePool[]
     */
    protected function getCaches()
    {
        if (empty($this->caches)) {
            throw new NoPoolAvailableException('No valid cache pool available for the chain.');
        }

        return $this->caches;
    }

    protected function storeItemInCache(PhpCacheItem $item, $ttl)
    {
        echo 'store in cache' . \PHP_EOL;
        foreach ($this->getCaches() as $cacheKey => $cache) {
            echo $cacheKey . \PHP_EOL;
            try {
                $saved = $cache->storeItemInCache($item, $ttl);
                if ($saved) {
                    return true;
                    //[isHit, value, tags[], expirationTimestamp]

                }
            } catch (CachePoolException $e) {
                $this->log('error', 'Unable to save: ' . $e->getMessage());
                // $this->handleException($poolKey, __FUNCTION__, $e);
            } catch (\Exception $e) {
                $this->log('error', 'Unable to save: ' . $e->getMessage());
            }
        }
    }

    protected function fetchObjectFromCache($key)
    {
        foreach ($this->getCaches() as $cacheKey => $cache) {
            try {
                $item = $cache->fetchObjectFromCache($key);
                if ($item[0] === true) {
                    return $item;
                    //[isHit, value, tags[], expirationTimestamp]

                }
            } catch (CachePoolException $e) {
                $this->logger->error('Unable to fetch', $e);
                // $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return [
            false,
            null,
            [],
            null,
        ];
    }

    protected function clearAllObjectsFromCache()
    {
        foreach ($this->getCaches() as $cacheKey => $cache) {
            try {
                $cleared = $cache->clearAllObjectsFromCache();
                if (!$cleared) {
                    $this->logger->error('Unable to clear all objects from cache "' . $cacheKey . '"');
                    //return $item;
                    //[isHit, value, tags[], expirationTimestamp]

                }
            } catch (CachePoolException $e) {
                $this->logger->error('Unable to clear all objects from cache', $e);
                // $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }
    }

    protected function clearOneObjectFromCache($key)
    {
        foreach ($this->getCaches() as $cacheKey => $cache) {
            try {
                $cleared = $cache->clearOneObjectFromCache($key);
                if (!$cleared) {
                    $this->logger->error('Unable to clear one objects from cache "' . $cacheKey . '"');
                    //return $item;
                    //[isHit, value, tags[], expirationTimestamp]

                }
            } catch (CachePoolException $e) {
                $this->logger->error('Unable to clear one objects from cache', $e);
                // $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }
    }

    protected function getList($name)
    {
        throw new \Exception('Not implemented');
    }

    protected function removeList($name)
    {
        throw new \Exception('Not implemented');
    }

    protected function appendListItem($name, $key)
    {
        throw new \Exception('Not implemented');
    }

    protected function removeListItem($name, $key)
    {
        throw new \Exception('Not implemented');
    }
}