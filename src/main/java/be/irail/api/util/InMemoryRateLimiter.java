package be.irail.api.util;

import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.atomic.AtomicInteger;

/**
 * Simple rate limiter to control outgoing requests.
 */
public class InMemoryRateLimiter {
    private final Map<String, AtomicInteger> counters = new ConcurrentHashMap<>();
    private final int limitPerMinute;

    public InMemoryRateLimiter(int limitPerMinute) {
        this.limitPerMinute = limitPerMinute;
    }

    /**
     * Attempts to acquire a permit for a request.
     * 
     * @param key the bucket key (e.g., minute-based)
     * @return true if permit acquired, false if rate limit exceeded
     */
    public boolean tryAcquire(String key) {
        AtomicInteger counter = counters.computeIfAbsent(key, k -> new AtomicInteger(0));
        
        // Clean up old keys (simplistic)
        if (counters.size() > 100) {
            counters.clear(); // Extreme cleanup
            counter = counters.computeIfAbsent(key, k -> new AtomicInteger(0));
        }

        int current = counter.get();
        while (current < limitPerMinute) {
            if (counter.compareAndSet(current, current + 1)) {
                return true;
            }
            current = counter.get();
        }
        return false;
    }
    
    public int getCount(String key) {
        AtomicInteger counter = counters.get(key);
        return counter != null ? counter.get() : 0;
    }
}
