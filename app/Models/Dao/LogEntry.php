<?php

namespace Irail\Models\Dao;

use Illuminate\Support\Carbon;

class LogEntry
{
    private int $id;
    private string $queryType;
    private array $query;
    private ?array $result;
    private string $userAgent;
    private Carbon $createdAt;

    public function __construct(int $id, string $queryType, array $query, ?array $result, string $userAgent, Carbon $createdAt)
    {
        $this->id = $id;
        $this->queryType = $queryType;
        $this->query = $query;
        $this->result = $result;
        $this->userAgent = $userAgent;
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getQueryType(): string
    {
        return $this->queryType;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return array|null
     */
    public function getResult(): ?array
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }


}