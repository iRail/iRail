package be.irail.api.dto;

import java.time.Instant;
import java.time.OffsetDateTime;
import java.time.ZoneOffset;

/**
 * Container for data that has been cached.
 * Stores the cached value along with its creation and expiration timestamps.
 *
 * @param <T> the type of the cached data
 */
public class CachedData<T> {
    private final T value;
    private final long createdAt;
    private long expiresAt;

    /**
     * Constructs a new CachedData entry.
     *
     * @param value the data to be cached
     * @param ttl the time-to-live in seconds
     */
    public CachedData(T value, int ttl) {
        this.value = value;
        this.createdAt = Instant.now().getEpochSecond();
        this.expiresAt = this.createdAt + ttl;
    }

    /**
     * Gets the cached value.
     * @return the cached data
     */
    public T getValue() {
        return this.value;
    }

    /**
     * Gets the timestamp when this entry was created.
     * @return the creation datetime
     */
    public OffsetDateTime getCreatedAt() {
        return OffsetDateTime.ofInstant(Instant.ofEpochSecond(this.createdAt), ZoneOffset.UTC);
    }

    /**
     * Gets the timestamp when this entry expires.
     * @return the expiration datetime
     */
    public OffsetDateTime getExpiresAt() {
        return OffsetDateTime.ofInstant(Instant.ofEpochSecond(this.expiresAt), ZoneOffset.UTC);
    }

    /**
     * Sets a new expiration timestamp for this entry.
     *
     * @param expiresAt the new expiration timestamp (epoch seconds)
     * @return this instance for chaining
     */
    public CachedData<T> setExpiresAt(long expiresAt) {
        this.expiresAt = expiresAt;
        return this;
    }

    /**
     * Calculates the current age of this entry in seconds.
     * @return the age in seconds
     */
    public long getAge() {
        return Instant.now().getEpochSecond() - this.createdAt;
    }
}
