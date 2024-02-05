<?php

namespace Irail\Repositories\Gtfs\Models;

class JourneyWithOriginAndDestination
{
    private int $journeyNumber;
    private string $originStopId;
    private int $originDepartureTime;
    private string $destinationStopId;
    private int $destinationArrivalTime;
    private string $tripId;
    private string $vehicleType;

    /**
     * @param string $tripId The trip id
     * @param string $vehicleType The journey type
     * @param int    $vehicleNumber The journey number
     * @param string $originStopId The stop id of the first stop
     * @param int $originDepartureTime The time of departure at the first stop, as an offset in seconds from the journey start date at 00:00:00.
     * @param string $destinationStopId The stop id of the last stop
     * @param int $destinationArrivalTime The time of arrival at the last stop, as an offset in seconds from the journey start date at 00:00:00.
     */
    public function __construct(
        string $tripId,
        string $vehicleType,
        int $vehicleNumber,
        string $originStopId,
        int $originDepartureTime,
        string $destinationStopId,
        int $destinationArrivalTime
    ) {
        $this->tripId = $tripId;
        $this->journeyNumber = $vehicleNumber;
        $this->originStopId = $originStopId;
        $this->originDepartureTime = $originDepartureTime;
        $this->destinationStopId = $destinationStopId;
        $this->destinationArrivalTime = $destinationArrivalTime;
        $this->vehicleType = $vehicleType;
    }

    /**
     * The vehicle type, such as "IC" or "S32"
     * @return string
     */
    public function getJourneyType(): string
    {
        return $this->vehicleType;
    }

    /**
     * @return int
     */
    public function getJourneyNumber(): int
    {
        return $this->journeyNumber;
    }

    /**
     * @return string
     */
    public function getOriginStopId(): string
    {
        return $this->originStopId;
    }

    /**
     * @return int The time of departure at the first stop, as an offset in seconds from the journey start date at 00:00:00.
     */
    public function getOriginDepartureTimeOffset(): int
    {
        return $this->originDepartureTime;
    }

    /**
     * @return string
     */
    public function getDestinationStopId(): string
    {
        return $this->destinationStopId;
    }

    /**
     * @return int The time of departure at the first stop, as an offset in seconds from the journey start date at 00:00:00.
     */
    public function getDestinationArrivalTimeOffset(): int
    {
        return $this->destinationArrivalTime;
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
        return [
            "Train {$this->journeyNumber} from {$this->originStopId} ({$this->originDepartureTime->format('h:i:s')})"
            . " to {$this->destinationStopId} ({$this->destinationArrivalTime->format('h:i:s')})"
        ];
    }
}
