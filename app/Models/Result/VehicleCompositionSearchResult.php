<?php

namespace Irail\Models\Result;

use Irail\Models\Cachable;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\TrainComposition;

class VehicleCompositionSearchResult implements Cachable
{
    use CachableResult;

    /**
     * @var $segment TrainComposition[] A list of all segments with their own composition for this train ride.
     */
    private array $segments;
    private Vehicle $vehicle;

    /**
     * @param TrainComposition[] $segments
     */
    public function __construct(Vehicle $vehicle, array $segments)
    {
        $this->segments = $segments;
        $this->vehicle = $vehicle;
    }

    /**
     * @return TrainComposition[]
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }
}
