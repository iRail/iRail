<?php

namespace Irail\Models\Result;

use Irail\Models\DepartureOrArrival;
use Irail\Models\StationInfo;

class LiveboardSearchResult
{
    use Cachable;

    private StationInfo $station;
    /**
     * @var $stops DepartureOrArrival[]
     */
    private array $stops;

    /**
     * @param StationInfo          $station
     * @param DepartureOrArrival[] $stops
     */
    public function __construct(StationInfo $station, array $stops)
    {
        $this->station = $station;
        $this->stops = $stops;
    }

    /**
     * @return StationInfo
     */
    public function getStation(): StationInfo
    {
        return $this->station;
    }

    /**
     * @return DepartureOrArrival[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }


}
