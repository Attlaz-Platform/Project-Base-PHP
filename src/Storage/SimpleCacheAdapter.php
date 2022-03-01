<?php
declare(strict_types=1);

namespace Attlaz\Project\Storage;

use Psr\SimpleCache\CacheInterface;

class SimpleCacheAdapter implements CacheInterface
{
    private StorageEngine $storageEngine;

    public function __construct(StorageEngine $storageEngine)
    {
        $this->storageEngine = $storageEngine;
    }

    public function get($key, $default = null)
    {
        $result = $this->storageEngine->getItem($key);
        if (!\is_null($result)) {
            return $result->value;
        }
        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->storageEngine->setItem($key, $value, $ttl);
    }

    public function delete($key)
    {
        return $this->storageEngine->deleteItem($key);
    }

    public function clear()
    {
        return $this->storageEngine->clearPool('default');
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function has($key)
    {
        return $this->storageEngine->hasItem($key);
    }
}
