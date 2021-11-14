<?php

namespace Irail\Models\Request;

interface CacheableRequest
{
    public function getCacheId(): string;
}
