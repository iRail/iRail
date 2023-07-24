<?php

namespace Irail\Models\VehicleComposition;

use Irail\Models\StationInfo;

class TrainCompositionOnSegment
{
    private StationInfo $origin;

    private StationInfo $destination;

    private TrainComposition $composition;

    /**
     * @param StationInfo      $origin
     * @param StationInfo      $destination
     * @param TrainComposition $composition
     */
    public function __construct(StationInfo $origin, StationInfo $destination, TrainComposition $composition)
    {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->composition = $composition;
    }


    public function getOrigin(): StationInfo
    {
        return $this->origin;
    }

    public function getDestination(): StationInfo
    {
        return $this->destination;
    }

    public function getComposition(): TrainComposition
    {
        return $this->composition;
    }


}
