<?php

namespace Irail\Models;

class Journey
{
    /**
     * @var JourneyLeg[]
     */
    private array $legs;

    /**
     * @var String[]
     */
    private array $notes;

    /**
     * @var Message[]
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
    public function getDeparture(): DepartureOrArrival
    {
        return $this->legs[0]->getDeparture();
    }

    /**
     * @return DepartureOrArrival
     */
    public function getArrival(): DepartureOrArrival
    {
        return end($this->legs)->getArrival();
    }

    public function setLegs(array $trainsInConnection): void
    {
        $this->legs = $trainsInConnection;
    }

    public function getDurationSeconds(): int
    {
        return $this->legs[0]->getDeparture()->getRealtimeDateTime()
            ->diffInSeconds(end($this->legs)->getArrival()->getRealtimeDateTime());
    }

    /**
     * @return String[]
     */
    public function getNotes(): array
    {
        return $this->notes;
    }

    public function setNotes(array $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return Message[]
     */
    public function getServiceAlerts(): array
    {
        return $this->serviceAlerts;
    }

    public function setServiceAlerts(array $serviceAlerts): void
    {
        $this->serviceAlerts = $serviceAlerts;
    }
}
