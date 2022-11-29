<?php

namespace Irail\Models\Result;

use Irail\Models\DepartureOrArrival;
use Irail\Models\StationInfo;

class LiveboardSearchResult
{
    private StationInfo $station;
    private int $timestamp;
    /**
     * @var $stops DepartureOrArrival[]
     */
    private array $stops;

    /**
     * @param StationInfo          $station
     * @param int                  $timestamp
     * @param DepartureOrArrival[] $stops
     */
    public function __construct(int $timestamp, StationInfo $station, array $stops)
    {
        $this->station = $station;
        $this->timestamp = $timestamp;
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
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return DepartureOrArrival[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }


}
