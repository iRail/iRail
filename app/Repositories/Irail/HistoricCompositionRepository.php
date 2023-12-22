<?php

namespace Irail\Repositories\Irail;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\CompositionHistoryEntry;
use Irail\Models\Dao\StoredComposition;
use Irail\Models\Dao\StoredCompositionUnit;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\TrainCompositionOnSegment;
use Spatie\Async\Pool;
use stdClass;

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
     * @return StoredComposition[]
     */
    public function getHistoricComposition(string $journeyType, int $journeyNumber, Carbon $date): array
    {
        $rows = DB::select('SELECT CU.*, CUU.fromStationId, CUU.toStationId, CUU.position FROM CompositionUnitUsage CUU JOIN CompositionUnit CU on CU.uicCode = cuu.uicCode
         WHERE CUU.journeyType = ? AND CUU.journeyNumber = ? AND CUU.date = ? ORDER BY CUU.fromStationId, CUU.position', [$journeyType, $journeyNumber, $date]);
        if (count($rows) == 0) {
            return [];
        }

        $compositionsBySegment = [];
        foreach ($rows as $row) {
            $unit = $this->transformCompositionUnit($row);
            $fromStationId = $row->fromStationId;
            $toStationId = $row->fromStationId;
            $segmentKey = "$fromStationId-$toStationId";

            if (!key_exists($segmentKey, $compositionsBySegment)) {
                $compositionsBySegment[$segmentKey] = (new StoredComposition())
                    ->setFromStationId($fromStationId)
                    ->setToStationId($toStationId)
                    ->setUnits([])
                    ->setJourneyType($journeyType)
                    ->setJourneyNumber($journeyNumber)
                    ->setDate($date);

            }
            $compositionsBySegment[$segmentKey]->getUnits()[$row->position] = $unit;
        }
        return array_values($compositionsBySegment);
    }

    public function recordComposition(Vehicle $vehicle, TrainCompositionOnSegment $composition, Carbon $journeyStartDate)
    {
        if ($composition->getComposition()->getLength() < 2
            || $journeyStartDate->copy()->startOfDay()->isAfter(Carbon::now()->startOfDay())) {
            // Only record valid compositions for vehicles running today
            return;
        }
        $units = $composition->getComposition()->getUnits();
        $passengerCarriageCount = 0;
        $types = [];
        foreach ($units as $unit) {
            if ($unit->getSeatsFirstClass() + $unit->getSeatsSecondClass() > 0) {
                $passengerCarriageCount++;
            }
            $parentType = $unit->getMaterialType()->getParentType();
            if (!key_exists($parentType, $types)) {
                $types[$parentType] = 0;
            }
            $types[$parentType]++;
        }
        $primaryMaterialType = array_keys($types, max($types))
        DB::update('INSERT INTO CompositionHistory(
                               journeyType, journeyNumber, journeyStartDate, 
                               fromStationId, toStationId,
                               primaryMaterialType, passengerUnitCount
                               ) VALUES (?,?,?,?,?,?,?)', [
            $vehicle->getType(),
            $vehicle->getNumber(),
            $journeyStartDate,
            $composition->getOrigin()->getId(),
            $composition->getDestination()->getId(),
            $primaryMaterialType,
            $passengerCarriageCount
        ]);
    }


    private function transformCompositionHistory(StdClass $row): CompositionHistoryEntry
    {
        return (new CompositionHistoryEntry())
            ->setJourneyType($row->journeyType)
            ->setJourneyNumber($row->journeyNumber)
            ->setDate($row->journeyStartDate)
            ->setPrimaryMaterialType($row->primaryMaterialType)
            ->setPassengerUnitCount($row->passengerUnitCount)
            ->setCreatedAt($row->createdAt);

    }

    private function transformCompositionUnit(StdClass $row): StoredCompositionUnit
    {
        return (new StoredCompositionUnit())
            ->setUicCode($row->uicCode)
            ->setMaterialTypeName($row->materialTypeName)
            ->setMaterialSubTypeName($row->materialSubTypeName)
            ->setMaterialNumber($row->materialNumber)
            ->setHasToilet($row->hasToilet)
            ->setHasPrmToilet($row->hasPrmToilet)
            ->setHasAirco($row->hasAirco)
            ->setHasBikeSection($row->hasBikeSection)
            ->setHasPrmSection($row->hasPrmSection)
            ->setSeatsFirstClass($row->seatsFirstClass)
            ->setSeatsSecondClass($row->seatsSecondClass)
            ->setCreatedAt($row->createdAt)
            ->setUpdatedAt($row->updatedAt);
    }
}