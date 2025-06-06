<?php

declare(strict_types=1);

namespace Attlaz\Project\Storage;

use Attlaz\Client;
use Attlaz\Model\StorageItem;
use Attlaz\Project\App\Environment;

class StorageEngine
{
    private Client $attlazClient;
    private Environment $environment;
    private string $storageType;

    public function __construct(Client $attlazClient, Environment $environment, string $storageType)
    {
        $this->attlazClient = $attlazClient;
        $this->environment = $environment;
        $this->storageType = $storageType;
    }

    public function getItem(string $key, string|null $pool = null): StorageItem|null
    {
        return $this->attlazClient->getStorageEndpoint()->getItem($this->environment->getProjectEnvironment()->id, $this->storageType, $key, $pool);
    }

    public function hasItem(string $key, string|null $pool = null): bool
    {
        return $this->attlazClient->getStorageEndpoint()->hasItem($this->environment->getProjectEnvironment()->id, $this->storageType, $key, $pool);
    }

    public function setItem(string $key, mixed $value, int|null $expirationSeconds = null, string|null $pool = null): bool
    {
        // TODO: expiration seconds is required for cache
        // TODO: how to handle overrides?

        if (!\is_null($expirationSeconds) && $expirationSeconds <= 0) {
            if ($this->storageType !== 'cache') {
                // TODO: is this expected behaviour?
            }
            return false;
        }
        $item = new StorageItem();
        $item->key = $key;
        $item->value = $value;
        if ($expirationSeconds !== null) {
            $date = new \DateTime();
            $date->add(new \DateInterval('PT' . $expirationSeconds . 'S'));
            $item->expiration = $date;
        }

        return $this->attlazClient->getStorageEndpoint()->setItem($this->environment->getProjectEnvironment()->id, $this->storageType, $item, $pool);
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    public function getItemKeys(string|null $pool = null): array
    {
        return $this->attlazClient->getStorageEndpoint()->getItemKeys($this->environment->getProjectEnvironment()->id, $this->storageType, $pool);
    }

    public function deleteItem(string $key, string|null $pool = null): bool
    {
        return $this->attlazClient->getStorageEndpoint()->deleteItem($this->environment->getProjectEnvironment()->id, $this->storageType, $key, $pool);
    }

    public function deleteItems(array $keys, string|null $pool = null): array
    {
        return $this->attlazClient->getStorageEndpoint()->deleteItems($this->environment->getProjectEnvironment()->id, $this->storageType, $keys, $pool);
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    public function getPoolKeys(): array
    {
        return $this->attlazClient->getStorageEndpoint()->getPoolKeys($this->environment->getProjectEnvironment()->id, $this->storageType);
    }

    public function clearPool(string $pool): bool
    {
        return $this->attlazClient->getStorageEndpoint()->clearPool($this->environment->getProjectEnvironment()->id, $this->storageType, $pool);
    }
}
