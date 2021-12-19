<?php

namespace Irail\Models\Result;

use Irail\Models\DepartureAndArrival;
use Irail\Models\StationBoardEntry;
use Irail\Models\Vehicle;

class VehicleJourneyResult
{
    private Vehicle $vehicle;
    private array $stops;
    private int $timestamp;
    private array $alerts;

    /**
     * @param Vehicle               $vehicle
     * @param DepartureAndArrival[] $stops
     */
    public function __construct(int $timestamp, Vehicle $vehicle, array $stops, array $alerts)
    {
        $this->timestamp = $timestamp;
        $this->vehicle = $vehicle;
        $this->stops = $stops;
        $this->alerts = $alerts;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return Vehicle
     */
    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    /**
     * @return DepartureAndArrival[]
     */
    public function getStops(): array
    {
        return $this->stops;
    }

    /**
     * @param int $index
     * @return StationBoardEntry|null
     */
    public function getDeparture(int $index):?StationBoardEntry
    {
        return $this->getStops()[$index]->getDeparture();
    }

    /**
     * @param int $index
     * @return StationBoardEntry|null
     */
    public function getArrival(int $index) :?StationBoardEntry
    {
        return $this->getStops()[$index]->getArrival();
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }


}
