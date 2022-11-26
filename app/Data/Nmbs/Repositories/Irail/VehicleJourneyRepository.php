<?php

namespace Irail\Data\Nmbs\Repositories\Irail;

use Irail\Models\Requests\VehicleJourneyRequest;
use Irail\Models\Result\VehicleJourneyResult;

interface VehicleJourneyRepository
{
    public function getDatedVehicleJourney(VehicleJourneyRequest $request): VehicleJourneyResult;
}