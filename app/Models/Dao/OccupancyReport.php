<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;
use Irail\Models\OccupancyLevel;

class OccupancyReport
{
    private int $id;
    private string $vehicleId;
    private int $stopId;
    private Carbon $journeyStartDate;
    private string $source;
    private OccupancyLevel $occupancy;
    private Carbon $createdAt;

    /**
     * @param int            $id
     * @param string         $vehicleId
     * @param int            $stopId
     * @param Carbon         $journeyStartDate
     * @param string         $source
     * @param OccupancyLevel $occupancy
     * @param Carbon         $createdAt
     */
    public function __construct(int $id, string $vehicleId, int $stopId, Carbon $journeyStartDate, string $source, OccupancyLevel $occupancy, Carbon $createdAt)
    {
        $this->id = $id;
        $this->vehicleId = $vehicleId;
        $this->stopId = $stopId;
        $this->journeyStartDate = $journeyStartDate;
        $this->source = $source;
        $this->occupancy = $occupancy;
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getVehicleId(): string
    {
        return $this->vehicleId;
    }

    /**
     * @return int
     */
    public function getStopId(): int
    {
        return $this->stopId;
    }

    /**
     * @return Carbon
     */
    public function getJourneyStartDate(): Carbon
    {
        return $this->journeyStartDate;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return OccupancyLevel
     */
    public function getOccupancy(): OccupancyLevel
    {
        return $this->occupancy;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }
}
