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
    private object|string|array $value;
    private int $createdAt;
    private int $expiresAt;

    /**
     * @param string $key
     * @param T      $value
     */
    public function __construct(string $key, object|array|string $value)
    {
        $this->key = $key;
        $this->value = $value;
        $this->createdAt = time();
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