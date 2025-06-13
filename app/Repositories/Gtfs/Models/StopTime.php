<?php

namespace Irail\Repositories\Gtfs\Models;

use Carbon\Carbon;

class StopTime
{
    private string $stopId;
    private Carbon $arrivalTime;
    private Carbon $departureTime;
    private bool $alighting;
    private bool $boarding;

    /**
     * @param string $stopId
     * @param string $departureTime the departure time, in hh:mm:ss format
     */
    public function __construct(string $stopId, string $arrivalTime, string $departureTime, bool $boarding, bool $alighting)
    {
        $this->stopId = $stopId;
        $this->arrivalTime = Carbon::parse($arrivalTime);
        $this->departureTime = Carbon::parse($departureTime);
        $this->alighting = $alighting;
        $this->boarding = $boarding;
    }

    public function getStopId(): string
    {
        return $this->stopId;
    }

    public function getDepartureTime(): Carbon
    {
        return $this->departureTime;
    }

    public function getArrivalTime(): Carbon
    {
        return $this->arrivalTime;
    }

    /**
     * @return bool True if passengers can embark/disembark at this point. If false, this station is just a waypoint the train is merely passing by.
     */
    public function hasPassengerExchange(): bool
    {
        return $this->boarding || $this->alighting;
    }

}
