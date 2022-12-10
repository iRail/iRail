<?php

namespace Irail\Models;

class Journey
{
    /**
     * @var JourneyLeg[]
     */
    private array $legs;

    /**
     * @var Note[]
     */
    private array $notes;

    /**
     * @var array ServiceAlertNote[]
     */
    private array $serviceAlerts;

    public function setLegs(array $trainsInConnection)
    {
        $this->legs = $trainsInConnection;
    }

    public function getDurationSeconds(): int
    {
        return $this->legs[0]->getDeparture()->getRealtimeDateTime()
            ->diffInSeconds(end($this->legs)->getArrival()->getRealtimeDateTime());
    }

    public function setNotes(array $notes)
    {
        $this->notes = $notes;
    }
}
