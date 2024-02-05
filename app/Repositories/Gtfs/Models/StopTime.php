<?php

namespace Irail\Repositories\Gtfs\Models;

use Carbon\Carbon;

class StopTime
{
    private string $stopId;
    private int $departureTime;

    /**
     * @param string $stopId
     * @param string $stopTime the departure time, in hh:mm:ss format
     */
    public function __construct(string $stopId, string $stopTime)
    {
        $this->stopId = $stopId;
        $this->departureTime = $this->timeToSeconds($stopTime);
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

    private function timeToSeconds(string $stopTime)
    {
        $hours = intval(substr($stopTime, 0, 2));
        $minutes = intval(substr($stopTime, 3, 2));
        $seconds = intval(substr($stopTime, 6, 2));
        return $hours * 3600 + $minutes * 60 + $seconds;
    }


}