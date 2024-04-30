<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;

class StoredComposition
{
    private string $fromStationId;
    private string $toStationId;

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
     * @var StoredCompositionUnit[] $units
     */
    private array $units;

    public function getFromStationId(): string
    {
        return $this->fromStationId;
    }

    public function setFromStationId(string $fromStationId): StoredComposition
    {
        $this->fromStationId = $fromStationId;
        return $this;
    }

    public function getToStationId(): string
    {
        return $this->toStationId;
    }

    public function setToStationId(string $toStationId): StoredComposition
    {
        $this->toStationId = $toStationId;
        return $this;
    }

    public function getJourneyType(): string
    {
        return $this->journeyType;
    }

    public function setJourneyType(string $journeyType): StoredComposition
    {
        $this->journeyType = $journeyType;
        return $this;
    }

    public function getJourneyNumber(): int
    {
        return $this->journeyNumber;
    }

    public function setJourneyNumber(int $journeyNumber): StoredComposition
    {
        $this->journeyNumber = $journeyNumber;
        return $this;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function setDate(Carbon $date): StoredComposition
    {
        $this->date = $date;
        return $this;
    }

    public function &getUnits(): array
    {
        return $this->units;
    }

    public function setUnits(array $units): StoredComposition
    {
        $this->units = $units;
        return $this;
    }
}
