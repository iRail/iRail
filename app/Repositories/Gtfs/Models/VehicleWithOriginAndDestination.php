<?php

namespace Irail\Repositories\Gtfs\Models;

class VehicleWithOriginAndDestination
{
    private int $vehicleNumber;
    private string $originStopId;
    private string $destinationStopId;
    private string $tripId;
    private string $vehicleType;

    /**
     * @param string $tripId
     * @param string $vehicleType
     * @param int    $vehicleNumber
     * @param string $originStopId
     * @param string $destinationStopId
     */
    public function __construct(string $tripId, string $vehicleType, int $vehicleNumber, string $originStopId, string $destinationStopId)
    {
        $this->tripId = $tripId;
        $this->vehicleNumber = $vehicleNumber;
        $this->originStopId = $originStopId;
        $this->destinationStopId = $destinationStopId;
        $this->vehicleType = $vehicleType;
    }

    /**
     * The vehicle type, such as "IC" or "S32"
     * @return string
     */
    public function getVehicleType(): string
    {
        return $this->vehicleType;
    }

    /**
     * @return int
     */
    public function getVehicleNumber(): int
    {
        return $this->vehicleNumber;
    }

    /**
     * @return string
     */
    public function getOriginStopId(): string
    {
        return $this->originStopId;
    }

    /**
     * @return string
     */
    public function getDestinationStopId(): string
    {
        return $this->destinationStopId;
    }

    /**
     * @return string
     */
    public function getTripId(): string
    {
        return $this->tripId;
    }

    public function __debugInfo(): ?array
    {
        return ["Train {$this->vehicleNumber} from {$this->originStopId} to {$this->destinationStopId}"];
    }
}
