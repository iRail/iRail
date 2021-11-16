<?php

namespace Irail\Models\Result;

use Irail\Models\DepartureAndArrival;
use Irail\Models\StationInfo;
use Irail\Models\StationBoardEntry;

class LiveboardResult
{
    private StationInfo $station;
    private int $timestamp;
    /**
     * @var $stops StationBoardEntry[]
     */
    private array $stops;

    /**
     * @param StationInfo         $station
     * @param int                 $timestamp
     * @param StationBoardEntry[] $stops
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
     * @return StationBoardEntry[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }


}
