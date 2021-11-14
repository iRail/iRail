<?php

namespace Irail\Models;

use DateTime;

class DepartureArrival
{
    private string $uri;

    private StationInfo $station;
    private PlatformInfo $platform;
    private Vehicle $vehicle;
    private Occupancy $occupancy;

    private string $headsign;
    private array $direction;

    private ?DateTime $scheduledDepartureTime;
    private ?int $departureDelay;

    private ?DateTime $scheduledArrivalTime;
    private ?int $arrivalDelay;

    private bool $isCanceled;
    private bool $hasArrived;
    private bool $hasLeft;
    private bool $isExtraStop;

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return DepartureArrival
     */
    public function setUri(string $uri): DepartureArrival
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return StationInfo
     */
    public function getStation(): StationInfo
    {
        return $this->station;
    }

    /**
     * @param StationInfo $station
     * @return DepartureArrival
     */
    public function setStation(StationInfo $station): DepartureArrival
    {
        $this->station = $station;
        return $this;
    }

    /**
     * @return PlatformInfo
     */
    public function getPlatform(): PlatformInfo
    {
        return $this->platform;
    }

    /**
     * @param PlatformInfo $platform
     * @return DepartureArrival
     */
    public function setPlatform(PlatformInfo $platform): DepartureArrival
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return Vehicle
     */
    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    /**
     * @param Vehicle $vehicle
     * @return DepartureArrival
     */
    public function setVehicle(Vehicle $vehicle): DepartureArrival
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * @return Occupancy
     */
    public function getOccupancy(): Occupancy
    {
        return $this->occupancy;
    }

    /**
     * @param Occupancy $occupancy
     * @return DepartureArrival
     */
    public function setOccupancy(Occupancy $occupancy): DepartureArrival
    {
        $this->occupancy = $occupancy;
        return $this;
    }

    /**
     * This is an array, because a train might have multiple directions if it splits later in its journey!
     * @return StationInfo[]
     */
    public function getDirection(): array
    {
        return $this->direction;
    }

    /**
     * This is an array, because a train might have multiple directions if it splits later in its journey!
     *
     * @param StationInfo[] $direction
     * @return DepartureArrival
     */
    public function setDirection(array $direction): DepartureArrival
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * The headsign is a string, presented to travelers, and does not have to match one station.
     * @param string $headsign
     */
    public function setHeadsign(string $headsign)
    {
        $this->headsign = $headsign;
    }

    /**
     * The headsign is a string, presented to travelers, and does not have to match one station.
     * @return string
     */
    public function getHeadsign(): string
    {
        return $this->headsign;
    }


    /**
     * @return DateTime|null
     */
    public function getScheduledDepartureTime(): ?DateTime
    {
        return $this->scheduledDepartureTime;
    }

    /**
     * @param DateTime|null $scheduledDepartureTime
     * @return DepartureArrival
     */
    public function setScheduledDepartureTime(?DateTime $scheduledDepartureTime): DepartureArrival
    {
        $this->scheduledDepartureTime = $scheduledDepartureTime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDepartureDelay(): ?int
    {
        return $this->departureDelay;
    }

    /**
     * @param int|null $departureDelay
     * @return DepartureArrival
     */
    public function setDepartureDelay(?int $departureDelay): DepartureArrival
    {
        $this->departureDelay = $departureDelay;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getScheduledArrivalTime(): ?DateTime
    {
        return $this->scheduledArrivalTime;
    }

    /**
     * @param DateTime|null $scheduledArrivalTime
     * @return DepartureArrival
     */
    public function setScheduledArrivalTime(?DateTime $scheduledArrivalTime): DepartureArrival
    {
        $this->scheduledArrivalTime = $scheduledArrivalTime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getArrivalDelay(): ?int
    {
        return $this->arrivalDelay;
    }

    /**
     * @param int|null $arrivalDelay
     * @return DepartureArrival
     */
    public function setArrivalDelay(?int $arrivalDelay): DepartureArrival
    {
        $this->arrivalDelay = $arrivalDelay;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->isCanceled;
    }

    /**
     * @param bool $isCanceled
     * @return DepartureArrival
     */
    public function setIsCanceled(bool $isCanceled): DepartureArrival
    {
        $this->isCanceled = $isCanceled;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasArrived(): bool
    {
        return $this->hasLeft || $this->hasArrived;
    }

    /**
     * @param bool $hasArrived
     * @return DepartureArrival
     */
    public function setHasArrived(bool $hasArrived): DepartureArrival
    {
        $this->hasArrived = $hasArrived;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasLeft(): bool
    {
        return $this->hasLeft;
    }

    /**
     * @param bool $hasLeft
     * @return DepartureArrival
     */
    public function setHasLeft(bool $hasLeft): DepartureArrival
    {
        $this->hasLeft = $hasLeft;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExtraStop(): bool
    {
        return $this->isExtraStop;
    }

    /**
     * @param bool $isExtraStop
     * @return DepartureArrival
     */
    public function setIsExtraStop(bool $isExtraStop): DepartureArrival
    {
        $this->isExtraStop = $isExtraStop;
        return $this;
    }


}
