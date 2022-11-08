<?php

namespace Irail\Models;

use DateTime;
use Irail\Data\Nmbs\Models\Station;

/**
 * Base class for a vehicle calling at a stop, could be either a departure or arrival
 */
class StationBoardEntry
{
    private Vehicle $vehicle;
    private StationInfo $station;

    private ?PlatformInfo $platform;
    private DateTime $scheduledDateTime;
    private int $delay;
    private bool $isCancelled;

    private string $headSign;
    private Station $direction;

    private bool $isReported;
    private bool $isExtra;

    private ?string $uri;

    /**
     * @return StationInfo
     */
    public function getStation(): StationInfo
    {
        return $this->station;
    }

    /**
     * @param StationInfo $station
     * @return StationBoardEntry
     */
    public function setStation(StationInfo $station): StationBoardEntry
    {
        $this->station = $station;
        return $this;
    }


    /**
     * @return PlatformInfo|null
     */
    public function getPlatform(): ?PlatformInfo
    {
        return $this->platform;
    }

    public function isPlatformInfoAvailable(): bool
    {
        return $this->platform != null;
    }

    /**
     * @param PlatformInfo|null $platform
     * @return StationBoardEntry
     */
    public function setPlatform(?PlatformInfo $platform): StationBoardEntry
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getScheduledDateTime(): DateTime
    {
        return $this->scheduledDateTime;
    }

    /**
     * @param DateTime $scheduledDateTime
     * @return StationBoardEntry
     */
    public function setScheduledDateTime(DateTime $scheduledDateTime): StationBoardEntry
    {
        $this->scheduledDateTime = $scheduledDateTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     * @return StationBoardEntry
     */
    public function setDelay(int $delay): StationBoardEntry
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    /**
     * @param bool $isCancelled
     * @return StationBoardEntry
     */
    public function setIsCancelled(bool $isCancelled): StationBoardEntry
    {
        $this->isCancelled = $isCancelled;
        return $this;
    }

    /**
     * @return string
     */
    public function getHeadSign(): string
    {
        return $this->headSign;
    }

    /**
     * @param string $headSign
     * @return StationBoardEntry
     */
    public function setHeadSign(string $headSign): StationBoardEntry
    {
        $this->headSign = $headSign;
        return $this;
    }

    /**
     * @return array
     */
    public function getDirection(): Station
    {
        return $this->direction;
    }

    /**
     * @param array $direction
     * @return StationBoardEntry
     */
    public function setDirection(Station $direction): StationBoardEntry
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Whether this call has been reported as "passed" in realtime.
     * @return bool
     */
    public function isReported(): bool
    {
        return $this->isReported;
    }

    /**
     * @param bool $isReported
     * @return StationBoardEntry
     */
    public function setIsReported(bool $isReported): StationBoardEntry
    {
        $this->isReported = $isReported;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExtra(): bool
    {
        return $this->isExtra;
    }

    /**
     * @param bool $isExtra
     * @return StationBoardEntry
     */
    public function setIsExtra(bool $isExtra): StationBoardEntry
    {
        $this->isExtra = $isExtra;
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
     * @return StationBoardEntry
     */
    public function setVehicle(Vehicle $vehicle): StationBoardEntry
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @param string|null $uri
     * @return StationBoardEntry
     */
    public function setUri(?string $uri): StationBoardEntry
    {
        $this->uri = $uri;
        return $this;
    }

    public function setOccupany(?Occupancy $occupancy)
    {

    }

}