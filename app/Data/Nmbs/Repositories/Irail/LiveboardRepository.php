<?php

namespace Irail\Data\Nmbs\Repositories\Irail;

use Irail\Models\Requests\LiveboardRequest;
use Irail\Models\Result\LiveboardResult;

interface LiveboardRepository
{
    public function getLiveboard(LiveboardRequest $request): LiveboardResult;
}