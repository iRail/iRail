<?php

namespace Irail\Traits;

use APCUIterator;
use Cache\Adapter\Apcu\ApcuCachePool;
use Closure;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Models\Cachable;
use Irail\Models\CachedData;
use Psr\Cache\InvalidArgumentException;

/**
 * A trait adding methods to get and set cached items. It uses APC as an underlying cache store. All data is returned as CachedData, which contain the time
 * when the object was cached and how long it is valid. This feature is not available in the laravel Cache facade, therefore the APCU cache is addressed directly.
 */
trait Cache
{
    private static ?ApcuCachePool $cache = null;
    private string $prefix = '';
    private int $defaultTtl = 15;

    /**
     * @param string $cacheKey
     * @return string
     */
    public function getSynchronizationLockKey(string $cacheKey): string
    {
        return $this->addPrefixToKey($cacheKey) . '|synchronizedLock';
    }

    private function initializeCachePool(): void
    {
        if (self::$cache == null) {
            if (extension_loaded('apcu')) {
                self::$cache = new ApcuCachePool();
            } else {
                // APCu is required since we need to handle large amounts of GTFS data for most of the requests. Without a functioning in-memory cache which
                // is shared between processes, the iRail API will not deliver adequate performance. Since this is so important, we refuse running on wrongly
                // configured servers
                throw new InternalProcessingException(500, 'APCU Cache is not enabled, but required to run iRail');
            }
        }
    }

    /**
     * Set a prefix which will be added in front of any key submitted in any other method.
     * @param string $prefix
     * @return void
     */
    protected function setCachePrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Set the default Time to live (TTL) for objects stored in the cache.
     * @param int $defaultTtl The default time in seconds.
     */
    public function setDefaultTtl(int $defaultTtl): void
    {
        $this->defaultTtl = $defaultTtl;
    }

    public function isCached(string $key): bool
    {
        $prefixedKey = $this->addPrefixToKey($key);
        $this->initializeCachePool();
        try {
            return self::$cache->hasItem($prefixedKey);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get an item from the cache.
     *
     * @param string $key The key to search for.
     * @return object|false The cached object if found. If not found, false.
     */
    public function getCachedObject(string $key): CachedData|false
    {
        $prefixedKey = $this->addPrefixToKey($key);
        $this->initializeCachePool();

        try {
            if (self::$cache->hasItem($prefixedKey)) {
                return $this->getCacheEntry($prefixedKey);
            } else {
                return false;
            }
        } catch (InvalidArgumentException $e) {
            Log::error('getCachedObject: Failed to read from cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Store an item in the cache
     *
     * @template T, the cached data type
     *
     * @param string $key The key to store the object under
     * @param T      $value The object to store
     * @param int    $ttl The number of seconds to keep this in cache. 0 for infinity.
     *                      Negative values will be replaced with the default TTL value.
     * @return CachedData<T>
     */
    public function setCachedObject(string $key, null|object|string|array|bool $value, int $ttl = -1): CachedData
    {
        if ($ttl < 0) {
            $ttl = $this->defaultTtl;
        }

        $prefixedKey = $this->addPrefixToKey($key);
        $this->initializeCachePool();
        try {
            $item = self::$cache->getItem($prefixedKey);
        } catch (InvalidArgumentException $e) {
            Log::error('setCachedObject: Failed to read from cache: ' . $e->getMessage());
            throw new InternalProcessingException(500, 'Failed to read from cache: ' . $e->getMessage(), $e);
        }

        $cacheEntry = new CachedData($prefixedKey, $value, $ttl);
        $item->set(igbinary_serialize($cacheEntry));
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        self::$cache->save($item);
        return $cacheEntry;
    }

    /**
     * Update a cache entry with a new TTL/expiry date.
     * @param CachedData $cachedData
     * @return CachedData
     */
    public function update(CachedData $cachedData): CachedData
    {
        $this->initializeCachePool();
        try {
            $item = self::$cache->getItem($cachedData->getKey());
        } catch (InvalidArgumentException $e) {
            Log::error('update: Failed to read from cache: ' . $e->getMessage());
            throw new InternalProcessingException(500, 'Failed to read from cache: ' . $e->getMessage(), $e);
        }

        $item->set(igbinary_serialize($cachedData));
        if ($cachedData->getRemainingTtl() > 0) {
            $item->expiresAfter($cachedData->getRemainingTtl());
        }

        self::$cache->save($item);
        return $cachedData;
    }

    private function deleteCachedObject(string $key): string
    {
        $this->initializeCachePool();
        return self::$cache->delete($this->addPrefixToKey($key));
    }

    protected function deleteCachedObjectsByPrefix(string $prefix): void
    {
        $prefix = $this->addPrefixToKey($prefix);
        // increase chunk size since there can be up to 100.000 cached variables, which typically are small
        $iterator = new APCUIterator('/^' . $prefix . '/', APC_LIST_ACTIVE, 500);
        apcu_delete($iterator);
    }

    /**
     * Get data from the cache. If the data is not present in the cache, the value function will be called
     * and the retrieved value will be stored in the cache.
     *
     * @template T, the cached data type
     *
     * @param string  $cacheKey
     * @param Closure $valueProvider
     * @param int     $ttl The number of seconds to keep this in cache. 0 for infinity.
     *                      Negative values will be replaced with the default TTL value.
     * @return CachedData<T>
     */
    private function getCacheOrUpdate(string $cacheKey, Closure $valueProvider, int $ttl = -1): CachedData
    {
        $cachedData = $this->getCachedObject($cacheKey);
        if ($cachedData === false) {
            $data = $valueProvider();
            if ($data instanceof Cachable && $data->getExpiresAt() != null) {
                $newTtl = max($ttl, $data->getRemainingTtl());
                Log::debug("Adjusting TTL for cache item $cacheKey from $ttl to $newTtl based on cached content with ttls {$data->getRemainingTtl()}");
                $ttl = $newTtl;
            }
            $cachedData = $this->setCachedObject($cacheKey, $data, $ttl);
        }
        return $cachedData;
    }

    /**
     * Get data from the cache. If the data is not present in the cache, the value function will be called from 1 thread on a best-effort basis,
     * and the retrieved value will be stored in the cache.
     *
     * @template T, the cached data type
     *
     * @param string  $cacheKey
     * @param Closure $valueProvider
     * @param int     $ttl The number of seconds to keep this in cache. 0 for infinity.
     *                      Negative values will be replaced with the default TTL value.
     * @return CachedData<T>
     */
    private function getCacheOrSynchronizedUpdate(string $cacheKey, Closure $valueProvider, int $ttl = -1): CachedData
    {
        // If cached, return the cached data
        $cachedData = $this->getCachedObject($cacheKey);
        if ($cachedData !== false) {
            return $cachedData;
        }

        // Get the lock id and a ticket number for this request.
        $lockId = $this->getSynchronizationLockKey($cacheKey);
        $callerId = random_int(1, 999_999_999);

        Log::info("Thread (caller $callerId) requires access to lock with id $lockId");
        // While another request has this lock, wait
        while ($this->isCached($lockId)) {
            Log::debug("Thread (caller $callerId) waiting for lock with id $lockId to be released");
            sleep(1);
        }

        // Re-check if the data is available now
        $cachedData = $this->getCachedObject($cacheKey);
        if ($cachedData !== false) {
            Log::info("Thread (caller $callerId) does no longer require access to lock with id $lockId: data received while waiting for lock");
            return $cachedData;
        }

        // Data was not available. Lock this resource. We can't use real semaphores, so a best-effort solution using the cache will have to do
        $this->setCachedObject($lockId, $callerId, 30);
        usleep(1000 * random_int(1, 200));
        $cachedLock = $this->getCachedObject($lockId);

        // Check if we managed to keep the lock, or if another process tried to grab it at the same time. If not, exit.
        if ($cachedLock === false || $cachedLock->getValue() != $callerId) {
            Log::warning("Thread (caller $callerId) failed to obtain lock with id $lockId");
            throw new InternalProcessingException('Failed to obtain a resource lock. Please try again in a few seconds.');
        }
        Log::info("Thread (caller $callerId) obtained lock with id $lockId");

        // Now we have a unique lock, call the provider function
        $data = $valueProvider();
        $cachedData = $this->setCachedObject($cacheKey, $data, $ttl);

        // Free the lock
        $this->deleteCachedObject($lockId);
        Log::info("Thread (caller $callerId) released lock with id $lockId");

        // Return the result
        return $cachedData;
    }

    /**
     * Get a cache key with invalid tokens removed and the configured prefix added.
     * @param String $key
     * @return string
     */
    private function addPrefixToKey(string $key): string
    {
        return $this->cleanCacheKey($this->prefix . '|' . $key);
    }

    /**
     * @template T, the cached data type
     * @param string $prefixedKey
     * @return CachedData<T>|null
     * @throws InvalidArgumentException
     */
    private function getCacheEntry(string $prefixedKey): ?CachedData
    {
        $cacheItem = self::$cache->getItem($prefixedKey);
        $cacheEntry = igbinary_unserialize($cacheItem->get());
        if ($cacheEntry == null) {
            return null;
        }
        $cacheEntry->setExpiresAt($cacheItem->getExpirationTimestamp());
        return $cacheEntry;
    }

    private function cleanCacheKey(string $key): string
    {
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':', ' ', '-'], '_', $key);
    }
}
