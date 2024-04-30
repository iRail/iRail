<?php

namespace Irail\Repositories\Gtfs\Models;

use Carbon\Carbon;

class StopTime
{
    private string $stopId;
    private int $arrivalTime;
    private int $departureTime;
    private PickupDropoffType $dropoffType;
    private PickupDropoffType $pickupType;

    /**
     * @param string $stopId
     * @param string $departureTime the departure time, in hh:mm:ss format
     */
    public function __construct(string $stopId, string $arrivalTime, string $departureTime, PickupDropoffType $dropoffType, PickupDropoffType $pickupType)
    {
        $this->stopId = $stopId;
        $this->arrivalTime = $this->timeToSeconds($arrivalTime) - 3600; // Correct for offset in GTFS
        $this->departureTime = $this->timeToSeconds($departureTime) - 3600; // Correct for offset in GTFS
        $this->dropoffType = $dropoffType;
        $this->pickupType = $pickupType;
    }

    public function getStopId(): string
    {
        return $this->stopId;
    }

    public function getDepartureTime(Carbon $journeyStartDate): Carbon
    {
        return $journeyStartDate->copy()->startOfDay()->addSeconds($this->departureTime);
    }

    public function getDepartureTimeOffset(): int
    {
        return $this->departureTime;
    }

    public function getArrivalTimeOffset(): int
    {
        return $this->arrivalTime;
    }

    /**
     * @return bool True if passengers can embark/disembark at this point. If false, this station is just a waypoint the train is merely passing by.
     */
    public function hasPassengerExchange(): bool
    {
        return !$this->isOnlyPassingBy();
    }

    /**
     * @return bool True if this station is just a waypoint the train is merely passing by.
     */
    public function isOnlyPassingBy(): bool
    {
        return $this->pickupType == PickupDropoffType::NEVER && $this->dropoffType == PickupDropoffType::NEVER;
    }

    private function timeToSeconds(string $stopTime)
    {
        $hours = intval(substr($stopTime, 0, 2));
        $minutes = intval(substr($stopTime, 3, 2));
        $seconds = intval(substr($stopTime, 6, 2));
        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}
