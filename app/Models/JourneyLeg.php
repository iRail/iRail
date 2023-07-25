<?php

namespace Irail\Models;

use Carbon\CarbonPeriod;

class JourneyLeg
{
    private ?Vehicle $vehicle;
    private DepartureOrArrival $arrival;
    private DepartureOrArrival $departure;
    /**
     * @var DepartureAndArrival[]
     */
    private array $intermediateStops = [];
    private VehicleDirection $direction;
    private JourneyLegType $legType;
    /**
     * @var Message[]
     */
    private array $alerts = [];
    private bool $reachable;

    public function __construct(DepartureOrArrival $departure, DepartureOrArrival $arrival)
    {
        $this->departure = $departure;
        $this->arrival = $arrival;
    }

    /**
     * @return DepartureOrArrival
     */
    public function getDeparture(): DepartureOrArrival
    {
        return $this->departure;
    }

    public function setDeparture(DepartureOrArrival $departure): void
    {
        $this->departure = $departure;
    }

    /**
     * @return DepartureOrArrival
     */
    public function getArrival(): DepartureOrArrival
    {
        return $this->arrival;
    }

    public function setArrival(DepartureOrArrival $arrival): void
    {
        $this->arrival = $arrival;
    }

    public function getDuration(): CarbonPeriod
    {
        return $this->departure->getRealtimeDateTime()->secondsUntil($this->arrival->getRealtimeDateTime());
    }

    public function getDurationSeconds(): int
    {
        return $this->getDeparture()->getRealtimeDateTime()
            ->diffInSeconds($this->getArrival()->getRealtimeDateTime());
    }

    public function setIntermediateStops(array $intermediateStops): void
    {
        $this->intermediateStops = $intermediateStops;
    }

    /**
     * @return DepartureAndArrival[];
     */
    public function getIntermediateStops(): array
    {
        return $this->intermediateStops;
    }

    /**
     * @return Vehicle|null
     */
    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    /**
     * @param Vehicle|null $vehicle
     */
    public function setVehicle(?Vehicle $vehicle): void
    {
        $this->vehicle = $vehicle;
        $this->departure->setVehicle($vehicle);
        $this->arrival->setVehicle($vehicle);
    }

    /**
     * @return JourneyLegType
     */
    public function getLegType(): JourneyLegType
    {
        return $this->legType;
    }

    public function setLegType(JourneyLegType $legType): void
    {
        $this->legType = $legType;
    }

    /**
     * @return Message[]
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }

    public function setAlerts(array $alerts): void
    {
        $this->alerts = $alerts;
    }

    /**
     * When a previous leg gets delayed, a following leg may become unreachable if there is insufficient time to
     * transfer in the station, or if the previous leg arrives after the departure of the vehicle in this leg.
     *
     * @return bool False if this leg cannot be reached before departure.
     */
    public function isReachable(): bool
    {
        return $this->reachable;
    }

    public function setReachable(bool $reachable)
    {
        $this->reachable = $reachable;
    }

}
