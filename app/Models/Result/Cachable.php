<?php

namespace Irail\Models\Result;

use Carbon\Carbon;

trait Cachable
{
    public function mergeCacheValidity(Carbon $createdAt, Carbon $expiresAt): void
    {
    }
}
