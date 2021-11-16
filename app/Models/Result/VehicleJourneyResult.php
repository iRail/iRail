<?php

namespace Irail\Models\Result;

use Irail\Models\Vehicle;

class VehicleJourneyResult
{
    private Vehicle $vehicle;
    private array $stops;
    private int $timestamp;
    private array $alerts;

    /**
     * @param Vehicle $vehicle
     * @param array   $stops
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
     * @return array
     */
    public function getStops(): array
    {
        return $this->stops;
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }


}
