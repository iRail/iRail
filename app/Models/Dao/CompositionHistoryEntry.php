<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Brief information about the composition of a train for a given day, recorded in the database.
 */
class CompositionHistoryEntry extends Model
{
    protected $table = 'CompositionHistory';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = null;

    /**
     * @return string $journeyType The journey type, for example "IC" in IC 513
     */
    public function getJourneyType(): string
    {
        return $this->getAttribute('journeyType');
    }

    public function setJourneyType(string $journeyType): CompositionHistoryEntry
    {
        $this->setAttribute('journeyType', $journeyType);
        return $this;
    }

    /**
     * @return int $journeyNumber The journey number, for example "513" in IC 513
     */
    public function getJourneyNumber(): int
    {
        return $this->getAttribute('journeyNumber');
    }

    public function setJourneyNumber(int $journeyNumber): CompositionHistoryEntry
    {
        $this->setAttribute('journeyNumber', $journeyNumber);
        return $this;
    }

    /**
     * @return Carbon $date The date on which this journey ran
     */
    public function getDate(): Carbon
    {
        return $this->getAttribute('journeyStartDate');
    }

    public function setJourneyStartDate(Carbon $date): CompositionHistoryEntry
    {
        $this->setAttribute('journeyStartDate', $date);
        return $this;
    }

    /**
     * @return string The id of the station from which this unit has the given position in the composition. Typically the first station of the journey, but might differ in case of trains which split.
     */
    public function getFromStationId(): string
    {
        return $this->getAttribute('fromStationId');
    }

    public function setFromStationId(string $fromStationId): CompositionHistoryEntry
    {
        $this->setAttribute('fromStationId', $fromStationId);
        return $this;
    }

    /**
     * @return string The id of the station up to which this unit has the given position in the composition. Typically the last station of the journey, but might differ in case of trains which split.
     */
    public function getToStationId(): string
    {
        return $this->getAttribute('toStationId');
    }

    public function setToStationId(string $toStationId): CompositionHistoryEntry
    {
        $this->setAttribute('toStationId', $toStationId);
        return $this;
    }

    /**
     * @return string $primaryMaterialType The type of the majority of the used journeys, such as I10, M6 or AM96
     */
    public function getPrimaryMaterialType(): string
    {
        return $this->getAttribute('primaryMaterialType');
    }

    public function setPrimaryMaterialType(string $primaryMaterialType): CompositionHistoryEntry
    {
        $this->setAttribute('primaryMaterialType', $primaryMaterialType);
        return $this;
    }

    /**
     * @return int $passengerUnitCount The number of units in which *passengers* can be seated
     */
    public function getPassengerUnitCount(): int
    {
        return $this->getAttribute('passengerUnitCount');
    }

    public function setPassengerUnitCount(int $passengerUnitCount): CompositionHistoryEntry
    {
        $this->setAttribute('passengerUnitCount', $passengerUnitCount);
        return $this;
    }

    /**
     * @return Carbon $createdAt The time when this composition was recorded
     */
    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute('createdAt');
    }

    // setCreatedAt is provided by the parent class

    public function getId(): int
    {
        return $this->getAttribute('id');
    }

}