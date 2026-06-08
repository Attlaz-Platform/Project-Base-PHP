<?php

declare(strict_types=1);

namespace Attlaz\Project\Storage;

use Attlaz\Client;
use Attlaz\Model\CursorPagination;
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

    public function getItem(string $key, string|null $bucket = null): StorageItem|null
    {
        return $this->attlazClient->getStorageEndpoint()->getItem($this->environment->getProjectEnvironment()->id, $this->storageType, $key, $bucket);
    }

    public function hasItem(string $key, string|null $bucket = null): bool
    {
        return $this->attlazClient->getStorageEndpoint()->hasItem($this->environment->getProjectEnvironment()->id, $this->storageType, $key, $bucket);
    }

    public function setItem(string $key, mixed $value, int|null $expirationSeconds = null, string|null $bucket = null): bool
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

        return $this->attlazClient->getStorageEndpoint()->setItem($this->environment->getProjectEnvironment()->id, $this->storageType, $item, $bucket);
    }

    /**
     * Return every item key in the (optional) pool.
     *
     * The client only exposes the paginated items-information listing
     * (getBucketItemsInformation); this walks every page using the record id as the
     * cursor and projects each record to its key, so the whole pool is returned
     * regardless of size (the previous single-call version silently returned only
     * the first page).
     *
     * @return string[]
     * @throws \Exception
     */
    public function getItemKeys(string|null $bucket = null): array
    {
        $endpoint = $this->attlazClient->getStorageEndpoint();
        $projectEnvironmentId = $this->environment->getProjectEnvironment()->id;

        $pagination = new CursorPagination();
        $pagination->limit = 1000;

        $keys = [];
        do {
            $page = $endpoint->getBucketItemsInformation($projectEnvironmentId, $this->storageType, $bucket, $pagination);

            $lastId = null;
            foreach ($page->getData() as $information) {
                $keys[] = $information->key;
                // The record id drives the cursor for the next page.
                if ($information->id !== null) {
                    $lastId = $information->id;
                }
            }
            $pagination->startingAfter = $lastId;
            // Stop when there are no more pages, or we can't determine the next cursor.
        } while ($page->hasMore && $lastId !== null);

        return $keys;
    }

    public function deleteItem(string $key, string|null $bucket = null): bool
    {
        return $this->attlazClient->getStorageEndpoint()->deleteItem($this->environment->getProjectEnvironment()->id, $this->storageType, $key, $bucket);
    }

    /**
     * @param string[] $keys
     * @return array<string, bool> map of item key => deleted
     */
    public function deleteItems(array $keys, string|null $bucket = null): array
    {
        return $this->attlazClient->getStorageEndpoint()->deleteItems($this->environment->getProjectEnvironment()->id, $this->storageType, $keys, $bucket);
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    public function getBucketKeys(): array
    {
        return $this->attlazClient->getStorageEndpoint()->getBucketKeys($this->environment->getProjectEnvironment()->id, $this->storageType);
    }

    public function clearBucket(string $bucket): bool
    {
        return $this->attlazClient->getStorageEndpoint()->clearBucket($this->environment->getProjectEnvironment()->id, $this->storageType, $bucket);
    }

    /**
     * @return string[]
     * @deprecated Renamed to getBucketKeys().
     */
    public function getPoolKeys(): array
    {
        return $this->getBucketKeys();
    }

    /** @deprecated Renamed to clearBucket(). */
    public function clearPool(string $bucket): bool
    {
        return $this->clearBucket($bucket);
    }
}
