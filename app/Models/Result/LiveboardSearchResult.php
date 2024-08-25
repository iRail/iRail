<?php

namespace Irail\Models\Result;

use Irail\Models\Cachable;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Station;

class LiveboardSearchResult implements Cachable
{
    use CachableResult;

    private Station $station;
    /**
     * @var $stops DepartureOrArrival[]
     */
    private array $stops;

    /**
     * @param Station $station
     * @param DepartureOrArrival[] $stops
     */
    public function __construct(Station $station, array $stops)
    {
        $this->station = $station;
        $this->stops = $stops;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
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
