<?php

namespace Irail\Traits;

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\Common\AbstractCachePool;
use Closure;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Models\CachedData;
use Psr\Cache\InvalidArgumentException;

trait Cache
{
    private static ?AbstractCachePool $cache = null;
    private string $prefix = '';
    private int $defaultTtl = 15;

    private function initializeCachePool(): void
    {
        if (self::$cache == null) {
            // Try to use APC when available
            if (extension_loaded('apcu')) {
                self::$cache = new ApcuCachePool();
            } else {
                throw new InternalProcessingException(500, 'APCU Cache is not enabled, but required to run iRail');
            }
        }
    }

    protected function setCachePrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @param int $defaultTtl
     */
    public function setDefaultTtl(int $defaultTtl): void
    {
        $this->defaultTtl = $defaultTtl;
    }

    public function isCached(string $key): bool
    {
        $prefixedKey = $this->getKeyWithPrefix($key);
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
        $prefixedKey = $this->getKeyWithPrefix($key);
        $this->initializeCachePool();

        try {
            if (self::$cache->hasItem($prefixedKey)) {
                return $this->getCacheEntry($prefixedKey);
            } else {
                return false;
            }
        } catch (InvalidArgumentException $e) {
            Log::error('Failed to read from cache: ' . $e->getMessage());
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
    public function setCachedObject(string $key, null|object|string|array $value, int $ttl = -1): CachedData
    {
        if ($ttl < 0) {
            $ttl = $this->defaultTtl;
        }

        $prefixedKey = $this->getKeyWithPrefix($key);
        $this->initializeCachePool();
        try {
            $item = self::$cache->getItem($prefixedKey);
        } catch (InvalidArgumentException $e) {
            Log::error('Failed to read from cache: ' . $e->getMessage());
            throw new InternalProcessingException(500, 'Failed to read from cache: ' . $e->getMessage(), $e);
        }

        $cacheEntry = new CachedData($key, $value, $ttl);
        $item->set($cacheEntry);
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        self::$cache->save($item);
        return $cacheEntry;
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
    private function getCacheWithDefaultCacheUpdate(string $cacheKey, Closure $valueProvider, int $ttl = -1): CachedData
    {
        $cachedData = $this->getCachedObject($cacheKey);
        if ($cachedData === false) {
            $data = $valueProvider();
            $cachedData = $this->setCachedObject($cacheKey, $data, $ttl);
        }
        return $cachedData;
    }

    /**
     * @param String $key
     * @return string
     */
    private function getKeyWithPrefix(string $key): string
    {
        return $this->cleanCacheKey($this->prefix . '|' . $key);
    }

    /**
     * @template T, the cached data type
     * @param string $key
     * @return CachedData<T>|null
     * @throws InvalidArgumentException
     */
    private function getCacheEntry(string $key): ?CachedData
    {
        $cacheItem = self::$cache->getItem($key);
        $cacheEntry = $cacheItem->get();
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
