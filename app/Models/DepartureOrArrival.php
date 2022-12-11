<?php

namespace Irail\Models;

use Carbon\Carbon;

/**
 * Base class for a vehicle calling at a stop, could be either a departure or arrival
 */
class DepartureOrArrival
{
    private Vehicle $vehicle;
    private StationInfo $station;

    private ?PlatformInfo $platform;
    private Carbon $scheduledDateTime;
    private int $delay;
    private bool $isCancelled;

    private string $headSign;
    private StationInfo $direction;

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
     * @return DepartureOrArrival
     */
    public function setStation(StationInfo $station): DepartureOrArrival
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
     * @return DepartureOrArrival
     */
    public function setPlatform(?PlatformInfo $platform): DepartureOrArrival
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * @return Carbon
     */
    public function getScheduledDateTime(): Carbon
    {
        return $this->scheduledDateTime;
    }

    /**
     * @return Carbon
     */
    public function getRealtimeDateTime(): Carbon
    {
        return $this->scheduledDateTime->copy()->addSeconds($this->delay);
    }

    /**
     * @param Carbon $scheduledDateTime
     * @return DepartureOrArrival
     */
    public function setScheduledDateTime(Carbon $scheduledDateTime): DepartureOrArrival
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
     * @param int $delay the delay in seconds.
     * @return DepartureOrArrival
     */
    public function setDelay(int $delay): DepartureOrArrival
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
     * @return DepartureOrArrival
     */
    public function setIsCancelled(bool $isCancelled): DepartureOrArrival
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
     * @return DepartureOrArrival
     */
    public function setHeadSign(string $headSign): DepartureOrArrival
    {
        $this->headSign = $headSign;
        return $this;
    }

    /**
     * @return StationInfo
     */
    public function getDirection(): StationInfo
    {
        return $this->direction;
    }

    /**
     * @param StationInfo $direction
     * @return DepartureOrArrival
     */
    public function setDirection(StationInfo $direction): DepartureOrArrival
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
     * @return DepartureOrArrival
     */
    public function setIsReported(bool $isReported): DepartureOrArrival
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
     * @return DepartureOrArrival
     */
    public function setIsExtra(bool $isExtra): DepartureOrArrival
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
     * @return DepartureOrArrival
     */
    public function setVehicle(Vehicle $vehicle): DepartureOrArrival
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDepartureUri(): ?string
    {
        return 'http://irail.be/connections/' . substr($this->station->getId(), 2) . '/' .
            date('Ymd', $this->scheduledDateTime) . '/' .
            str_replace(' ', '', $this->vehicle->getName());
    }

    public function setOccupany(?Occupancy $occupancy)
    {

    }

}