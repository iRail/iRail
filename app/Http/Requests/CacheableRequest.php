<?php

namespace Irail\Http\Requests;

interface CacheableRequest
{
    public function getCacheId(): string;
}
