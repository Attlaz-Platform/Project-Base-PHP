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

    public function get(string $key, mixed $default = null): mixed
    {
        $result = $this->storageEngine->getItem($key);
        if (!\is_null($result)) {
            return $result->value;
        }
        return $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->storageEngine->setItem($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->storageEngine->deleteItem($key);
    }

    public function clear(): bool
    {
        return $this->storageEngine->clearPool('default');
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->storageEngine->hasItem($key);
    }
}
