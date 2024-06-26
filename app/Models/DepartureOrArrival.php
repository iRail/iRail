<?php

namespace Irail\Models;

use Carbon\Carbon;

/**
 * Base class for a vehicle calling at a stop, could be either a departure or arrival
 */
class DepartureOrArrival
{
    private ?Vehicle $vehicle;
    private Station $station;

    private ?PlatformInfo $platform = null;
    private Carbon $scheduledDateTime;
    private int $delay = 0;
    private bool $isCancelled = false;

    private bool $isReported = false;
    private bool $isExtra = false;

    private ?DepartureArrivalState $status = null;

    private ?OccupancyInfo $occupancy = null;

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @param Station $station
     * @return DepartureOrArrival
     */
    public function setStation(Station $station): DepartureOrArrival
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
     * @return Vehicle|null The vehicle, or null in case of a walking departure/arrival.
     */
    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    /**
     * @param Vehicle|null $vehicle The vehicle, or null in case of a walking departure/arrival.
     * @return DepartureOrArrival
     */
    public function setVehicle(?Vehicle $vehicle): DepartureOrArrival
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDepartureUri(): ?string
    {
        if ($this->vehicle == null) {
            return null; // Not available on walking legs
        }
        return 'http://irail.be/connections/' . substr($this->station->getId(), 2) . '/' .
            date('Ymd', $this->scheduledDateTime->getTimestamp()) . '/' .
            str_replace(' ', '', $this->vehicle->getName());
    }

    public function getOccupancy(): OccupancyInfo
    {
        return $this->occupancy ?: new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN);
    }

    public function setOccupancy(OccupancyInfo $occupancy): void
    {
        $this->occupancy = $occupancy;
    }

    public function setStatus(?DepartureArrivalState $status)
    {
        $this->status = $status;
    }

    public function getStatus(): ?DepartureArrivalState
    {
        return $this->status;
    }
}
