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
     * @var ServiceAlertNote[]
     */
    private array $serviceAlerts;

    /**
     * @return JourneyLeg[]
     */
    public function getLegs(): array
    {
        return $this->legs;
    }

    /**
     * @return DepartureOrArrival
     */
    public function getDeparture()
    {
        return $this->legs[0]->getDeparture();
    }

    /**
     * @return DepartureOrArrival
     */
    public function getArrival()
    {
        return end($this->legs)->getArrival();
    }

    public function setLegs(array $trainsInConnection)
    {
        $this->legs = $trainsInConnection;
    }

    public function getDurationSeconds(): int
    {
        return $this->legs[0]->getDeparture()->getRealtimeDateTime()
            ->diffInSeconds(end($this->legs)->getArrival()->getRealtimeDateTime());
    }

    /**
     * @return Note[]
     */
    public function getNotes():array{
        return $this->notes;
    }

    public function setNotes(array $notes)
    {
        $this->notes = $notes;
    }
}
