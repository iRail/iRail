<?php

namespace Irail\api\data\models\hafas;

class VehicleWithOriginAndDestination
{
    private int $vehicleNumber;
    private string $originStopId;
    private string $destinationStopId;

    /**
     * @param int    $vehicleNumber
     * @param string $originStopId
     * @param string $destinationStopId
     */
    public function __construct(int $vehicleNumber, string $originStopId, string $destinationStopId)
    {
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


}