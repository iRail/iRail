<?php

namespace Irail\Traits;

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Closure;
use Psr\Cache\InvalidArgumentException;

trait Cache
{
    private AbstractCachePool $cache;
    private string $prefix;
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
        return $this->cache->hasItem($key);
    }

    /**
     * Get an item from the cache.
     *
     * @param string $key The key to search for.
     * @return object|false The cached object if found. If not found, false.
     */
    public function getCachedObject(string $key): object|false
    {
        $key = $this->getKeyWithPrefix($key);
        $this->initializeCachePool();

        try {
            if ($this->cache->hasItem($key)) {
                return $this->cache->getItem($key)->get();
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
     * @param string $key The key to store the object under
     * @param object $value The object to store
     * @param int    $ttl The number of seconds to keep this in cache. 0 for infinity.
     *                      Negative values will be replaced with the default TTL value.
     */
    public function setCachedObject(string $key, object $value, int $ttl = -1)
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

        $item->set($value);
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        $this->cache->save($item);
    }

    /**
     * Get data from the cache. If the data is not present in the cache, the value function will be called
     * and the retrieved value will be stored in the cache.
     * @param string   $cacheKey
     * @param Closure $valueProvider
     * @return false|object|void
     */
    private function getCacheWithDefaultCacheUpdate(string $cacheKey, Closure $valueProvider)
    {
        if ($this->isCached($cacheKey)) {
            return $this->getCachedObject($cacheKey);
        }
        $data = $valueProvider();
        $this->setCachedObject($cacheKey, $data);
        return $data;
    }

    /**
     * @param String $key
     * @return string
     */
    private function getKeyWithPrefix(string $key): string
    {
        return $this->prefix . $key;
    }

}
