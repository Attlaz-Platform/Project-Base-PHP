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
        $result = $this->storageEngine->getValue($key);
        if (!\is_null($result)) {
            return $result->value;
        }
        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->storageEngine->setValue($key, $value, $ttl);
    }

    public function delete($key)
    {
        return $this->storageEngine->delValue($key);
    }

    public function clear()
    {
        return $this->storageEngine->clear();
    }

    public function getMultiple($keys, $default = null)
    {
        // TODO: Implement getMultiple() method.
    }

    public function setMultiple($values, $ttl = null)
    {
        // TODO: Implement setMultiple() method.
    }

    public function deleteMultiple($keys)
    {
        // TODO: Implement deleteMultiple() method.
    }

    public function has($key)
    {
        return $this->storageEngine->hasValue($key);
    }
}
