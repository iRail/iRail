<?php

namespace Irail\api\data\models\hafas;

class VehicleWithOriginAndDestination
{
    private int $vehicleNumber;
    private string $originStopId;
    private string $destinationStopId;
    private string $tripId;

    /**
     * @param int    $vehicleNumber
     * @param string $originStopId
     * @param string $destinationStopId
     */
    public function __construct(string $tripId, int $vehicleNumber, string $originStopId, string $destinationStopId)
    {
        $this->tripId = $tripId;
        $this->vehicleNumber = $vehicleNumber;
        $this->originStopId = $originStopId;
        $this->destinationStopId = $destinationStopId;
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
