<?php

namespace Irail\Models;
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
     * @return int the timestamp at which this entry was created
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
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