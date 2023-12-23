<?php

namespace Irail\Repositories;

use Irail\Exceptions\CompositionUnavailableException;
use Irail\Http\Requests\VehicleCompositionRequest;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\Result\VehicleCompositionSearchResult;

interface VehicleCompositionRepository
{
    /**
     * Get the composition of a vehicle.
     *
     * @param VehicleCompositionRequest|VehicleJourneyRequest $request
     * @return VehicleCompositionSearchResult The response data. Null if no composition is available.
     * @throws CompositionUnavailableException
     */
    function getComposition(VehicleCompositionRequest|VehicleJourneyRequest $request): VehicleCompositionSearchResult;
}