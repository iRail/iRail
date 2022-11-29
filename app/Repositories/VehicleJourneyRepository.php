<?php

namespace Irail\Repositories;

use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\Result\VehicleJourneySearchResult;

interface VehicleJourneyRepository
{
    public function getDatedVehicleJourney(VehicleJourneyRequest $request): VehicleJourneySearchResult;
}