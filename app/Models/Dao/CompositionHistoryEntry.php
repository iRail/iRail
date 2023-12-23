<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;

/**
 * Brief information about the composition of a train for a given day, recorded in the database.
 */
class CompositionHistoryEntry
{
    /**
     * @var string $journeyType The journey type, for example "IC" in IC 513
     */
    private string $journeyType;

    /**
     * @var int $journeyNumber The journey number, for example "513" in IC 513
     */
    private int $journeyNumber;

    /**
     * @var Carbon $date The date on which this journey ran
     */
    private Carbon $date;

    /**
     * @var string The id of the station from which this unit has the given position in the composition. Typically the first station of the journey, but might differ in case of trains which split.
     */
    private string $fromStationId;

    /**
     * @var string The id of the station up to which this unit has the given position in the composition. Typically the last station of the journey, but might differ in case of trains which split.
     */
    private string $toStationId;

    /**
     * @var string $primaryMaterialType The type of the majority of the used journeys, such as I10, M6 or AM96
     */
    private string $primaryMaterialType;

    /**
     * @var int $passengerUnitCount The number of units in which *passengers* can be seated
     */
    private int $passengerUnitCount;

    /**
     * @var Carbon $createdAt The time when this composition was recorded
     */
    private Carbon $createdAt;

    public function getJourneyType(): string
    {
        return $this->journeyType;
    }

    public function setJourneyType(string $journeyType): CompositionHistoryEntry
    {
        $this->journeyType = $journeyType;
        return $this;
    }

    public function getJourneyNumber(): int
    {
        return $this->journeyNumber;
    }

    public function setJourneyNumber(int $journeyNumber): CompositionHistoryEntry
    {
        $this->journeyNumber = $journeyNumber;
        return $this;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function setDate(Carbon $date): CompositionHistoryEntry
    {
        $this->date = $date;
        return $this;
    }

    public function getFromStationId(): string
    {
        return $this->fromStationId;
    }

    public function setFromStationId(string $fromStationId): CompositionHistoryEntry
    {
        $this->fromStationId = $fromStationId;
        return $this;
    }

    public function getToStationId(): string
    {
        return $this->toStationId;
    }

    public function setToStationId(string $toStationId): CompositionHistoryEntry
    {
        $this->toStationId = $toStationId;
        return $this;
    }

    public function getPrimaryMaterialType(): string
    {
        return $this->primaryMaterialType;
    }

    public function setPrimaryMaterialType(string $primaryMaterialType): CompositionHistoryEntry
    {
        $this->primaryMaterialType = $primaryMaterialType;
        return $this;
    }

    public function getPassengerUnitCount(): int
    {
        return $this->passengerUnitCount;
    }

    public function setPassengerUnitCount(int $passengerUnitCount): CompositionHistoryEntry
    {
        $this->passengerUnitCount = $passengerUnitCount;
        return $this;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): CompositionHistoryEntry
    {
        $this->createdAt = $createdAt;
        return $this;
    }

}