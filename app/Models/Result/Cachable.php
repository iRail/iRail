<?php

namespace Irail\Models\Result;

use Carbon\Carbon;

trait Cachable
{
    function mergeCacheValidity(Carbon $createdAt, Carbon $expiresAt): void
    {

    }
}