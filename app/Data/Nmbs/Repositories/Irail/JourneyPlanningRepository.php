<?php

namespace Irail\Data\Nmbs\Repositories\Irail;

use Irail\Models\Result\TripResult;

interface JourneyPlanningRepository
{
    public function getJourneyPlanning(ConnectionsRequest $connectionsRequest): TripResult;
}