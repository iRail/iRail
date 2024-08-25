<?php

namespace Irail\Models\Result;

use Carbon\Carbon;

trait CachableResult
{
    private ?Carbon $createdAt = null;
    private ?Carbon $expiresAt = null;

    public function mergeCacheValidity(Carbon $createdAt, ?Carbon $expiresAt): void
    {
        if ($this->createdAt == null || $this->createdAt->isAfter($createdAt)) {
            $this->createdAt = $createdAt;
        }
        if ($this->expiresAt == null && $expiresAt == null) {
            // keep null
        } elseif ($this->expiresAt == null || $this->expiresAt->isBefore($expiresAt)) {
            $this->expiresAt = $expiresAt;
        }
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?Carbon
    {
        return $this->expiresAt;
    }

    public function getRemainingTtl(): ?int
    {
        if ($this->expiresAt == null) {
            return null;
        }
        return $this->expiresAt->timestamp - time();
    }
}
