<?php

namespace Irail\Repositories;

use Irail\Exceptions\CompositionUnavailableException;
use Irail\Http\Requests\VehicleCompositionRequest;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Vehicle;

interface VehicleCompositionRepository
{
    /**
     * Get the composition of a vehicle.
     *
     * @param VehicleCompositionRequest|Vehicle $request
     * @return VehicleCompositionSearchResult The response data. Null if no composition is available.
     * @throws CompositionUnavailableException
     */
    function getComposition(VehicleCompositionRequest|Vehicle $request): VehicleCompositionSearchResult;
}