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
    private ?string $splitsAtStopId;

    /**
     * @param string      $tripId The trip id
     * @param string      $vehicleType The journey type
     * @param int         $vehicleNumber The journey number
     * @param string      $originStopId The stop id of the first stop
     * @param int         $originDepartureTime The time of departure at the first stop, as an offset in seconds from the journey start date at 00:00:00.
     * @param string      $destinationStopId The stop id of the last stop
     * @param int         $destinationArrivalTime The time of arrival at the last stop, as an offset in seconds from the journey start date at 00:00:00.
     * @param string|null $splitsAtStopId The stop at which this train splits or joins. e.g. IC1 (A->C) and IC 2 (B->C) come together and form IC1 (C->D), in which case C should be in this field.
     */
    public function __construct(
        string $tripId,
        string $vehicleType,
        int $vehicleNumber,
        string $originStopId,
        int $originDepartureTime,
        string $destinationStopId,
        int $destinationArrivalTime,
        ?string $splitsAtStopId = null
    ) {
        $this->tripId = $tripId;
        $this->journeyNumber = $vehicleNumber;
        $this->originStopId = $originStopId;
        $this->originDepartureTime = $originDepartureTime;
        $this->destinationStopId = $destinationStopId;
        $this->destinationArrivalTime = $destinationArrivalTime;
        $this->vehicleType = $vehicleType;
        $this->splitsAtStopId = $splitsAtStopId;
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

    /**
     * The stop at which this train splits or joins. e.g. IC1 (A->C) and IC 2 (B->C) come together and form IC1 (C->D), in which case C should be in this field.
     * @return string|null Null if this train does not split/join
     */
    public function getSplitsAtStopId(): ?string
    {
        return $this->splitsAtStopId;
    }



    public function __debugInfo(): ?array
    {
        $formatSeconds = fn($seconds) => sprintf('%02d:%02d:%02d', ($seconds / 3600), ($seconds / 60 % 60), $seconds % 60);
        return [
            "Train {$this->journeyNumber} from {$this->originStopId} ({$formatSeconds($this->originDepartureTime)})"
            . " to {$this->destinationStopId} ({$formatSeconds($this->destinationArrivalTime)})"
        ];
    }
}
