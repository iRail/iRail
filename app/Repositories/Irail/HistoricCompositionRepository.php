<?php

namespace Irail\Repositories\Irail;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\CompositionHistoryEntry;
use Irail\Models\Dao\StoredComposition;
use Irail\Models\Dao\StoredCompositionUnit;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\TrainCompositionOnSegment;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
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
        $rows = DB::select('SELECT * FROM CompositionHistory WHERE journeyType = ? AND journeyNumber = ? AND journeyStartDate >= ?',
            [$vehicleType, $journeyNumber, Carbon::now()->startOfDay()->subDays($daysBack)]);
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
        $rows = DB::select('SELECT CU.*, CH.fromStationId, CH.toStationId, CUU.position 
            FROM CompositionHistory CH  
            JOIN CompositionUnitUsage CUU on CH.id = CUU.historicCompositionId
            JOIN CompositionUnit CU on CU.uicCode = cuu.uicCode
            WHERE CH.journeyType = ? AND CH.journeyNumber = ? AND CH.journeyStartDate = ? 
            ORDER BY CH.fromStationId, CUU.position',
            [$journeyType, $journeyNumber, $date]);
        if (count($rows) == 0) {
            return [];
        }

        $compositionsBySegment = [];
        foreach ($rows as $row) {
            $unit = $this->transformCompositionUnit($row);
            $fromStationId = $row->fromStationId;
            $toStationId = $row->toStationId;
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

    public function recordCompositionAsync(Vehicle $vehicle, TrainCompositionOnSegment $composition, Carbon $journeyStartDate): void
    {
        $this->threadPool->add(function () use ($vehicle, $composition, $journeyStartDate) {
            $this->recordComposition($vehicle, $composition, $journeyStartDate);
        });
    }

    public function recordComposition(Vehicle $vehicle, TrainCompositionOnSegment $composition, Carbon $journeyStartDate): void
    {
        if ($composition->getComposition()->getLength() < 2
            || $journeyStartDate->copy()->startOfDay()->isAfter(Carbon::now()->startOfDay())) {
            // Only record valid compositions for vehicles running today
            return;
        }
        if ($this->getCompositionId($vehicle, $journeyStartDate, $composition->getOrigin()->getId(), $composition->getDestination()->getId()) != null) {
            return; // Don't overwrite existing compositions
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
        $primaryMaterialType = array_keys($types, max($types))[0];
        $compositionId = $this->insertComposition($vehicle, $journeyStartDate, $composition, $primaryMaterialType, $passengerCarriageCount);
        foreach ($units as $position => $unit) {
            $this->insertIfNotExists($unit);
            DB::update('INSERT INTO CompositionUnitUsage(uicCode, historicCompositionId, position) VALUES (?,?,?)', [
                $unit->getUicCode(),
                $compositionId,
                $position + 1
            ]);
        }
    }


    private function transformCompositionHistory(StdClass $row): CompositionHistoryEntry
    {
        return (new CompositionHistoryEntry())
            ->setJourneyType($row->journeyType)
            ->setJourneyNumber($row->journeyNumber)
            ->setDate(Carbon::createFromTimeString($row->journeyStartDate))
            ->setFromStationId($row->fromStationId)
            ->setToStationId($row->toStationId)
            ->setPrimaryMaterialType($row->primaryMaterialType)
            ->setPassengerUnitCount($row->passengerUnitCount)
            ->setCreatedAt(Carbon::createFromTimeString($row->createdAt));

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
            ->setCreatedAt(Carbon::createFromTimeString($row->createdAt))
            ->setUpdatedAt(Carbon::createFromTimeString($row->updatedAt));
    }

    /**
     * @param Vehicle $vehicle
     * @param Carbon $journeyStartDate
     * @param TrainCompositionOnSegment $composition
     * @param int|string $primaryMaterialType
     * @param int $passengerCarriageCount
     * @return int|mixed|null
     */
    public function insertComposition(
        Vehicle $vehicle,
        Carbon $journeyStartDate,
        TrainCompositionOnSegment $composition,
        int|string $primaryMaterialType,
        int $passengerCarriageCount
    ): mixed {
        DB::insert('INSERT INTO CompositionHistory(
                               journeyType, journeyNumber, journeyStartDate, 
                               fromStationId, toStationId,
                               primaryMaterialType, passengerUnitCount, createdAt
                               ) VALUES (?,?,?,?,?,?,?,?)', [
            $vehicle->getType(),
            $vehicle->getNumber(),
            $journeyStartDate,
            $composition->getOrigin()->getId(),
            $composition->getDestination()->getId(),
            $primaryMaterialType,
            $passengerCarriageCount,
            Carbon::now()
        ]);
        return $this->getCompositionId($vehicle, $journeyStartDate,
            $composition->getOrigin()->getId(),
            $composition->getDestination()->getId());
    }

    /**
     * @param TrainCompositionUnit $unit
     * @return void
     */
    private function insertIfNotExists(TrainCompositionUnit $unit): void
    {
        if (getenv('DB_CONNECTION') == 'sqlite') {
            DB::update('INSERT OR IGNORE INTO CompositionUnit(
                            uicCode, materialTypeName, materialSubTypeName, materialNumber, 
                            hasToilet, hasPrmToilet, hasAirco, hasBikeSection, hasPrmSection,
                            seatsFirstClass, seatsSecondClass, createdAt, updatedAt) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?); ', [
                $unit->getUicCode(),
                $unit->getMaterialType()->getParentType(),
                $unit->getMaterialType()->getSubType(),
                $unit->getMaterialNumber(),
                $unit->hasToilet(),
                $unit->hasPrmToilet(),
                $unit->hasAirco(),
                $unit->hasBikeSection(),
                $unit->hasPrmSection(),
                $unit->getSeatsFirstClass(),
                $unit->getSeatsSecondClass(),
                Carbon::now(),
                Carbon::now()
            ]);
        } else {
            DB::update('IF NOT EXISTS(SELECT 1 FROM CompositionUnit WHERE uicCode=?) BEGIN
                            INSERT INTO CompositionUnit(
                            uicCode, materialTypeName, materialSubTypeName, materialNumber, 
                            hasToilet, hasPrmToilet, hasAirco, hasBikeSection, hasPrmSection,
                            seatsFirstClass, seatsSecondClass, createdAt, updatedAt) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
                            END; ', [
                $unit->getUicCode(),
                $unit->getUicCode(),
                $unit->getMaterialType()->getParentType(),
                $unit->getMaterialType()->getSubType(),
                $unit->getMaterialNumber(),
                $unit->hasToilet(),
                $unit->hasPrmToilet(),
                $unit->hasAirco(),
                $unit->hasBikeSection(),
                $unit->hasPrmSection(),
                $unit->getSeatsFirstClass(),
                $unit->getSeatsSecondClass(),
                Carbon::now(),
                Carbon::now()
            ]);
        }
    }

    /**
     * @param Vehicle $vehicle
     * @param Carbon  $journeyStartDate
     * @param string  $fromId
     * @param string  $toId
     * @return int|null
     */
    public function getCompositionId(Vehicle $vehicle, Carbon $journeyStartDate, string $fromId, string $toId): ?int
    {
        $rows = DB::select('SELECT id FROM CompositionHistory WHERE journeyType=? AND journeyNumber=? AND journeyStartDate=? AND fromStationId=? AND toStationId=?',
            [
                $vehicle->getType(),
                $vehicle->getNumber(),
                $journeyStartDate,
                $fromId,
                $toId
            ]);
        if (empty($rows)) {
            return null;
        }
        return $rows[0]->id;
    }
}