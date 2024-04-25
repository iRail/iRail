<?php

namespace Irail\Models;
use Carbon\Carbon;

/**
 * Data which has been cached.
 *
 * @template T, the cached data type
 * @template-implements CachedData<T>
 */
class CachedData
{
    private string $key;
    private null|object|string|array|bool $value;
    private int $createdAt;
    private int $expiresAt;

    /**
     * @param string                        $key
     * @param object|array|string|bool|null $value
     * @param int                           $ttl
     */
    public function __construct(string $key, null|object|array|string|bool $value, int $ttl = 0)
    {
        $this->key = $key;
        $this->value = $value;
        $this->createdAt = time();
        $this->expiresAt = time() + $ttl;
    }

    /**
     * The key of this cache entry.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * The value of this cache entry.
     *
     * @return T
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return Carbon the timestamp at which this entry was created
     */
    public function getCreatedAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->createdAt);
    }

    /**
     * @return Carbon
     */
    public function getExpiresAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->expiresAt);
    }

    /**
     * @param int $expiresAt
     * @return CachedData
     */
    public function setExpiresAt(int $expiresAt): CachedData
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }


    /**
     * @return int get the age of this entry in seconds
     */
    public function getAge(): int
    {
        return time() - $this->createdAt;
    }


}