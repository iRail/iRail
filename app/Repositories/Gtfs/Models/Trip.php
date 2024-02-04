<?php

namespace Irail\Repositories\Gtfs\Models;

class Trip
{
    private string $tripId;
    private string $journeyType;
    private int $journeyNumber;

    /**
     * @param string $tripId
     * @param string $journeyType
     * @param int    $journeyNumber
     */
    public function __construct(string $tripId, string $journeyType, int $journeyNumber,)
    {
        $this->tripId = $tripId;
        $this->journeyType = $journeyType;
        $this->journeyNumber = $journeyNumber;
    }

    public function getTripId(): string
    {
        return $this->tripId;
    }

    public function getJourneyType(): string
    {
        return $this->journeyType;
    }

    public function getJourneyNumber(): int
    {
        return $this->journeyNumber;
    }


}