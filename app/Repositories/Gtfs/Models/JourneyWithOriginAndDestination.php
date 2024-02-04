<?php

namespace Irail\Repositories\Gtfs\Models;

use Carbon\Carbon;

class JourneyWithOriginAndDestination
{
    private int $journeyNumber;
    private string $originStopId;
    private Carbon $originDepartureTime;
    private string $destinationStopId;
    private Carbon $destinationArrivalTime;
    private string $tripId;
    private string $vehicleType;

    /**
     * @param string $tripId The trip id
     * @param string $vehicleType The journey type
     * @param int    $vehicleNumber The journey number
     * @param string $originStopId The stop id of the first stop
     * @param Carbon $originDepartureTime The time of departure at the first stop, as a carbon object in the year 0 with only a time component.
     * @param string $destinationStopId The stop id of the last stop
     * @param Carbon $destinationArrivalTime The time of arrival at the last stop, as a carbon object in the year 0 with only a time component.
     */
    public function __construct(
        string $tripId,
        string $vehicleType,
        int $vehicleNumber,
        string $originStopId,
        Carbon $originDepartureTime,
        string $destinationStopId,
        Carbon $destinationArrivalTime
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
     * @return Carbon The time of departure at the first stop, as a carbon object in the year 0 with only a time component.
     */
    public function getOriginDepartureTime(): Carbon
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
     * @return Carbon The time of arrival at the last stop, as a carbon object in the year 0 with only a time component.
     */
    public function getDestinationArrivalTime(): Carbon
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
