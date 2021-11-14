<?php

namespace Irail\Models\Requests;

interface CacheableRequest
{
    public function getCacheId(): string;
}
