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
}