<?php

declare(strict_types=1);

namespace Attlaz\Project\Cache;

use Cache\Adapter\Chain\Exception\NoPoolAvailableException;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\Exception\CachePoolException;
use Cache\Adapter\Common\PhpCacheItem;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class FailOverCachePool extends AbstractCachePool
{
    use LoggerTrait;

    /**
     * @type LoggerInterface
     */
    private $logger;
    /**
     * @type AbstractCachePool[]
     */
    private $caches;
    /**
     * @type array
     */
    private $options;

    /**
     * @param array $options {
     *
     * @type  bool $skip_on_failure If true we will remove a pool form the chain if it fails.
     * }
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['skip_on_failure'])) {
            $options['skip_on_failure'] = false;
        }
        if (!isset($options['remove_pool_on_failure'])) {
            $options['remove_pool_on_failure'] = false;
        }
        $this->options = $options;
    }

    public function addCachePool(string $name, AbstractCachePool $cachePool)
    {
        $this->caches[$name] = $cachePool;
    }
    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
    /** @noinspection PhpMissingParentCallCommonInspection */
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
            throw new CachePoolException('No valid cache pool available for the chain.');
        }

        return $this->caches;
    }

    protected function storeItemInCache(PhpCacheItem $item, $ttl)
    {
        /**
         * @var string $cacheName
         * @var AbstractCachePool $cachePool
         */
        foreach ($this->getCaches() as $cacheName => $cachePool) {
            try {
                $saved = $cachePool->storeItemInCache($item, $ttl);
                if ($saved) {
                    return true;
                    //[isHit, value, tags[], expirationTimestamp]
                } else {
                    $this->logger->warning('Unable to save entry to log, trying next log storage');
                }
            } catch (CachePoolException $e) {
                $this->log('error', 'Unable to save to ' . $cacheName . ': ' . $e->getMessage());
                // $this->handleException($poolKey, __FUNCTION__, $e);
            } catch (\Exception $e) {
                $this->log('error', 'Unable to save to ' . $cacheName . ': ' . $e->getMessage());
            }
        }

        return false;
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
        $c = 0;
        foreach ($this->getCaches() as $cacheKey => $cache) {
            try {
                $cleared = $cache->clearAllObjectsFromCache();
                if (!$cleared) {
                    $this->logger->error('Unable to clear all objects from cache "' . $cacheKey . '"');
                    //return $item;
                    //[isHit, value, tags[], expirationTimestamp]
                } else {
                    $c++;
                }
            } catch (CachePoolException $e) {
                $this->logger->error('Unable to clear all objects from cache', $e);
                // $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $c === count($this->getCaches());
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
