<?php

namespace Irail\Repositories;

use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Models\Result\JourneyPlanningSearchResult;

interface JourneyPlanningRepository
{
    public function getJourneyPlanning(JourneyPlanningRequest $connectionsRequest): JourneyPlanningSearchResult;
}