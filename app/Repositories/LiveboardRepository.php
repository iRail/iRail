<?php

namespace Irail\Repositories;

use Irail\Http\Requests\LiveboardRequest;
use Irail\Models\Result\LiveboardSearchResult;

interface LiveboardRepository
{
    public function getLiveboard(LiveboardRequest $request): LiveboardSearchResult;
}
