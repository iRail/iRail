<?php

namespace Irail\Repositories;

use Irail\Exceptions\CompositionUnavailableException;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Vehicle;

interface VehicleCompositionRepository
{
    /**
     * Get the composition of a vehicle.
     *
     * @param Vehicle $request
     * @return VehicleCompositionSearchResult The response data. Null if no composition is available.
     * @throws CompositionUnavailableException
     */
    public function getComposition(Vehicle $request): VehicleCompositionSearchResult;
}
