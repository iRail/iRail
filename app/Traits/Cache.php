<?php

namespace Irail\Traits;

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Closure;
use Irail\Models\CachedData;
use Psr\Cache\InvalidArgumentException;

trait Cache
{
    private ?AbstractCachePool $cache = null;
    private string $prefix = '';
    private int $defaultTtl = 15;

    private function initializeCachePool(): void
    {
        if ($this->cache == null) {
            // Try to use APC when available
            if (extension_loaded('apcu')) {
                $this->cache = new ApcuCachePool();
            } else {
                // Fall back to array cache
                $this->cache = new ArrayCachePool();
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
        $key = $this->getKeyWithPrefix($key);
        $this->initializeCachePool();
        try {
            return $this->cache->hasItem($key);
        } catch (InvalidArgumentException) {
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
        $key = $this->getKeyWithPrefix($key);
        $this->initializeCachePool();

        try {
            if ($this->cache->hasItem($key)) {
                return $this->getCacheEntry($key);
            } else {
                return false;
            }
        } catch (InvalidArgumentException $e) {
            // Todo: log something here
            return false;
        }
    }

    /**
     * Store an item in the cache
     *
     * @param string              $key The key to store the object under
     * @param object|string|array $value The object to store
     * @param int                 $ttl The number of seconds to keep this in cache. 0 for infinity.
     *                      Negative values will be replaced with the default TTL value.
     */
    public function setCachedObject(string $key, object|string|array $value, int $ttl = -1)
    {
        if ($ttl < 0) {
            $ttl = $this->defaultTtl;
        }

        $key = $this->getKeyWithPrefix($key);
        $this->initializeCachePool();
        try {
            $item = $this->cache->getItem($key);
        } catch (InvalidArgumentException $e) {
            // Todo: log something here
            return;
        }

        $cacheEntry = new CachedData($key, $value);
        $item->set($cacheEntry);
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        $this->cache->save($item);
    }

    /**
     * Get data from the cache. If the data is not present in the cache, the value function will be called
     * and the retrieved value will be stored in the cache.
     * @param string  $cacheKey
     * @param Closure $valueProvider
     * @param int     $ttl The number of seconds to keep this in cache. 0 for infinity.
     *                      Negative values will be replaced with the default TTL value.
     * @return CachedData
     */
    private function getCacheWithDefaultCacheUpdate(string $cacheKey, Closure $valueProvider, int $ttl = -1): CachedData
    {
        if (!$this->isCached($cacheKey)) {
            $data = $valueProvider();
            $this->setCachedObject($cacheKey, $data, $ttl);
        }
        return $this->getCachedObject($cacheKey);
    }

    /**
     * @param String $key
     * @return string
     */
    private function getKeyWithPrefix(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * @param string $key
     * @return CachedData|null
     * @throws InvalidArgumentException
     */
    private function getCacheEntry(string $key): ?CachedData
    {
        $cacheItem = $this->cache->getItem($key);
        $cacheEntry = $cacheItem->get();
        if ($cacheEntry == null) {
            return null;
        }
        $cacheEntry->setExpiresAt($cacheItem->getExpirationTimestamp());
        return $cacheEntry;
    }
}
