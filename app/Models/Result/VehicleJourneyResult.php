<?php

namespace Irail\Models\Result;

use Irail\Data\Nmbs\Models\VehicleInfo;
use stdClass;

class VehicleJourneyResult
{
    private VehicleInfo $vehicle;
    private array $stops;
    private int $timestamp;
    private array $alerts;

    /**
     * @param VehicleInfo $vehicle
     * @param array       $stops
     */
    public function __construct(int $timestamp, VehicleInfo $vehicle, array $stops, array $alerts)
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
     * @return VehicleInfo
     */
    public function getVehicle(): VehicleInfo
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
