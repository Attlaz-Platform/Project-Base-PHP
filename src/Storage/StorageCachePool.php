<?php

namespace Attlaz\Project\Storage;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;

class StorageCachePool extends AbstractCachePool
{
    /** @var StorageEngine */
    private $storageEngine;

    public function __construct(StorageEngine $storageEngine)
    {
        $this->storageEngine = $storageEngine;
    }

    protected function storeItemInCache(PhpCacheItem $item, $ttl)
    {
        $this->storageEngine->setItem($item->getKey(), $item, $ttl, 'default');
    }

    protected function fetchObjectFromCache($key)
    {
        $item = $this->storageEngine->getItem($key);
        if (!\is_null($item)) {
            // isHit, value, tags[], expirationTimestamp
            return [true, $item->value, [], $item->expiration];
        }
        return null;
    }

    protected function clearAllObjectsFromCache()
    {
        return $this->storageEngine->clearPool('default');
    }

    protected function clearOneObjectFromCache($key)
    {
        return $this->storageEngine->deleteItem($key, 'default');
    }

    protected function getList($name)
    {
        throw new \Exception('Get list not implemented');
    }

    protected function removeList($name)
    {
        throw new \Exception('Remove list not implemented');
    }

    protected function appendListItem($name, $key)
    {
        throw new \Exception('Append list item not implemented');
    }

    protected function removeListItem($name, $key)
    {
        throw new \Exception('Remove list item not implemented');
    }
}
