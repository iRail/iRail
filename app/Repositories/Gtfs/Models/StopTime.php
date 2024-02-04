<?php

namespace Irail\Repositories\Gtfs\Models;

use Carbon\Carbon;

class StopTime
{
    private string $stopId;
    private Carbon $stopTime;

    /**
     * @param string $stopId
     * @param Carbon $stopTime
     */
    public function __construct(string $stopId, Carbon $stopTime)
    {
        $this->stopId = $stopId;
        $this->stopTime = $stopTime;
    }

    public function getStopId(): string
    {
        return $this->stopId;
    }

    public function getStopTime(): Carbon
    {
        return $this->stopTime;
    }


}