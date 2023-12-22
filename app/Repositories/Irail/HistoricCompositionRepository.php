<?php

namespace Irail\Repositories\Irail;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\CompositionHistoryEntry;
use Irail\Models\Dao\StoredComposition;
use Irail\Models\Dao\StoredCompositionUnit;
use Spatie\Async\Pool;

class HistoricCompositionRepository
{

    private Pool $threadPool;

    public function __construct()
    {
        $this->threadPool = Pool::create();
    }

    /**
     * Get a list of all rolling stock known in the iRail system.
     *
     * @return StoredCompositionUnit[]
     */
    public function getAllUnits(): array
    {
        $rows = DB::select('SELECT * FROM CompositionUnit ORDER BY uicCode DESC');
        return array_map(fn($row) => $this->transformCompositionUnit($row), $rows);
    }

    /**
     * Get a list of historic compositions on a coarse level (vehicle type and carriage count).
     *
     * @return CompositionHistoryEntry[]
     */
    public function getHistoricCompositions(string $vehicleType, int $journeyNumber, int $daysBack = 21): array
    {
        $rows = DB::select('SELECT * FROM CompositionHistory WHERE journeyType = ? AND journeyNumber = ? AND date = ?');
        if (count($rows) == 0) {
            return [];
        }
        return array_map(fn($row) => $this->transformCompositionHistory($row), $rows);
    }


    /**
     * Get the complete historic composition for a journey on a given day.
     *
     * @return StoredComposition
     */
    public function getHistoricComposition(string $vehicleType, int $journeyNumber, Carbon $date): ?StoredComposition
    {
        $rows = DB::select('SELECT * FROM CompositionUnitUsage CUU JOIN irail.CompositionUnit CU on CU.uicCode = cuu.uicCode
         WHERE CUU.journeyType = ? AND CUU.journeyNumber = ? AND CUU.date = ?');
        if (count($rows) == 0) {
            return null;
        }
        $units = array_map(fn($row) => $this->transformCompositionUnit($row), $rows);
        $result = new StoredComposition();
        // TODO: implement
        return $result;
    }


    private function transformCompositionHistory(array $row): CompositionHistoryEntry
    {
        // TODO: implement
    }

    private function transformCompositionUnit(array $row): StoredCompositionUnit
    {
        // TODO: implement
    }
}