package be.irail.api.util;

import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

/**
 * A simple in-memory cache with TTL support.
 *
 * @param <T> the type of data to cache
 */
public class InMemoryCache<T> {
    private final Map<String, CacheEntry<T>> cache = new ConcurrentHashMap<>();

    /**
     * Puts a value in the cache with a specific TTL.
     *
     * @param key        the cache key
     * @param value      the value to cache
     * @param ttlSeconds TTL in seconds
     */
    public void put(String key, T value, int ttlSeconds) {
        long expiresAt = ttlSeconds > 0 ? System.currentTimeMillis() + (ttlSeconds * 1000L) : Long.MAX_VALUE;
        cache.put(key, new CacheEntry<>(value, expiresAt));
    }

    /**
     * Gets a value from the cache if it exists and has not expired.
     *
     * @param key the cache key
     * @return the cached value, or null if not found or expired
     */
    public T get(String key) {
        CacheEntry<T> entry = cache.get(key);
        if (entry == null) {
            return null;
        }

        if (System.currentTimeMillis() > entry.expiresAt) {
            cache.remove(key);
            return null;
        }

        return entry.value;
    }

    /**
     * Clears all entries from the cache.
     */
    public void clear() {
        cache.clear();
    }

    public boolean has(String cacheId) {
        return cache.containsKey(cacheId);
    }

    private record CacheEntry<T>(T value, long expiresAt) {
    }
}
