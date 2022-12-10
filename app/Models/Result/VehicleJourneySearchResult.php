<?php

namespace Irail\Models\Result;

use Irail\Models\DepartureAndArrival;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Vehicle;

class VehicleJourneySearchResult
{
    use Cachable;

    private Vehicle $vehicle;
    private array $stops;
    private array $alerts;

    /**
     * @param Vehicle $vehicle
     * @param array   $stops
     * @param array   $alerts
     */
    public function __construct(Vehicle $vehicle, array $stops, array $alerts)
    {
        $this->vehicle = $vehicle;
        $this->stops = $stops;
        $this->alerts = $alerts;
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
     * @return DepartureOrArrival|null
     */
    public function getDeparture(int $index): ?DepartureOrArrival
    {
        return $this->getStops()[$index]->getDeparture();
    }

    /**
     * @param int $index
     * @return DepartureOrArrival|null
     */
    public function getArrival(int $index): ?DepartureOrArrival
    {
        return $this->getStops()[$index]->getArrival();
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }


}
