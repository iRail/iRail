<?php

namespace Irail\Repositories\Irail;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\CompositionHistoryEntry;
use Irail\Models\Dao\CompositionStatistics;
use Irail\Models\Dao\StoredComposition;
use Irail\Models\Dao\StoredCompositionUnit;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use stdClass;

class HistoricCompositionRepository
{

    public function __construct()
    {
    }

    /**
     * Get a list of all rolling stock known in the iRail system.
     *
     * @return StoredCompositionUnit[]
     */
    public function getAllUnits(): array
    {
        return StoredCompositionUnit::all()->orderBy('uicCode', 'desc')->all();
    }

    /**
     * Get a list of historic compositions on a coarse level (vehicle type and carriage count).
     *
     * @return CompositionHistoryEntry[]
     */
    public function getHistoricCompositions(string $vehicleType, int $journeyNumber, int $daysBack = 21): array
    {
        return CompositionHistoryEntry::where(
            [
                ['journeyType' => $vehicleType],
                ['journeyNumber' => $journeyNumber],
                ['journeyStartDate', '>=', Carbon::now()->startOfDay()->subDays($daysBack)]
            ])->get()->all();
    }

    /**
     * @param string $vehicleType
     * @param int    $journeyNumber
     * @param int    $daysBack
     * @return CompositionStatistics[]
     */
    public function getHistoricCompositionStatistics(string $vehicleType, int $journeyNumber, int $daysBack = 21): array
    {
        $compositions = $this->getHistoricCompositions(
            $vehicleType,
            $journeyNumber,
            $daysBack);
        $lengths = array_map(fn($comp) => $comp->getPassengerUnitCount(), $compositions);
        sort($lengths);
        $lengthFrequency = array_count_values($lengths);
        $typesFrequency = array_count_values(array_map(fn($comp) => $comp->getPrimaryMaterialType(), $compositions));
        $medianLength = $this->median($lengths);
        $mostProbableLength = array_keys($lengthFrequency, max($lengthFrequency))[0];
        $lengthPercentage = 100 * max($lengthFrequency) / count($compositions);
        $primaryMaterialType = array_keys($typesFrequency, max($typesFrequency))[0];
        $typePercentage = 100 * max($lengthFrequency) / count($compositions);
        return [
            new CompositionStatistics(count($compositions), $medianLength, $mostProbableLength, $lengthPercentage,
                $primaryMaterialType, $typePercentage)
        ];
    }

    /**
     * Get the complete historic composition for a journey on a given day.
     *
     * @return StoredComposition[]
     */
    public function getHistoricComposition(Vehicle $vehicle): array
    {
        $rows = DB::select('SELECT CU.*, CH.fromStationId, CH.toStationId, CUU.position 
            FROM CompositionHistory CH  
            JOIN CompositionUnitUsage CUU on CH.id = CUU.historicCompositionId
            JOIN CompositionUnit CU on CU.uicCode = cuu.uicCode
            WHERE CH.journeyType = ? AND CH.journeyNumber = ? AND CH.journeyStartDate = ? 
            ORDER BY CH.fromStationId, CUU.position',
            [$vehicle->getType(), $vehicle->getNumber(), $vehicle->getJourneyStartDate()]);
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
                    ->setJourneyType($vehicle->getType())
                    ->setJourneyNumber($vehicle->getNumber())
                    ->setDate($vehicle->getJourneyStartDate());
            }
            $compositionsBySegment[$segmentKey]->getUnits()[$row->position] = $unit;
        }
        return array_values($compositionsBySegment);
    }


    public function recordComposition(VehicleCompositionSearchResult|TrainComposition $composition): void
    {
        // Allow recording entire responses by recursively recording every composition in the result
        if ($composition instanceof VehicleCompositionSearchResult) {
            foreach ($composition->getSegments() as $segment) {
                $this->recordComposition($segment);
            }
            return;
        }

        if ($composition->getLength() < 2
            || $composition->getVehicle()->getJourneyStartDate()->isAfter(Carbon::now()->startOfDay())) {
            // Only record valid compositions for vehicles running today
            return;
        }
        if ($this->getCompositionId($composition) != null) {
            return; // Don't overwrite existing compositions
        }

        $units = $composition->getUnits();
        $passengerCarriageCount = 0;

        foreach ($units as $unit) {
            if ($unit->getSeatsFirstClass() + $unit->getSeatsSecondClass() > 0) {
                $passengerCarriageCount++;
            }
        }
        $typesFrequency = array_count_values(array_map(fn($unit) => $unit->getMaterialType()->getParentType(), $units));
        $primaryMaterialType = array_keys($typesFrequency, max($typesFrequency))[0];
        $compositionId = $this->insertComposition($composition, $primaryMaterialType, $passengerCarriageCount);
        foreach ($units as $position => $unit) {
            $this->insertIfNotExists($unit);
            DB::update('INSERT INTO CompositionUnitUsage(uicCode, historicCompositionId, position) VALUES (?,?,?)', [
                $unit->getUicCode(),
                $compositionId,
                $position + 1
            ]);
        }
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
            ->setCreatedAt(Carbon::parse($row->createdAt))
            ->setUpdatedAt(Carbon::parse($row->updatedAt));
    }

    /**
     * @param TrainComposition $composition
     * @param int|string       $primaryMaterialType
     * @param int              $passengerCarriageCount
     * @return int|null
     */
    public function insertComposition(
        TrainComposition $composition,
        int|string $primaryMaterialType,
        int $passengerCarriageCount
    ): ?int {
        $history = new CompositionHistoryEntry();
        $history->setJourneyType($composition->getVehicle()->getType());
        $history->setJourneyNumber($composition->getVehicle()->getNumber());
        $history->setJourneyStartDate($composition->getVehicle()->getJourneyStartDate());
        $history->setFromStationId($composition->getOrigin()->getId());
        $history->setToStationId($composition->getDestination()->getId());
        $history->setPrimaryMaterialType($primaryMaterialType);
        $history->setPassengerUnitCount($passengerCarriageCount);
        $history->save();
        return $history->getId();
    }

    /**
     * @param TrainCompositionUnit $unit
     * @return void
     */
    private function insertIfNotExists(TrainCompositionUnit $unit): void
    {
        if (StoredCompositionUnit::find($unit->getUicCode())) {
            return;
        }
        $storedUnit = new StoredCompositionUnit();
        $storedUnit->setUicCode($unit->getUicCode());
        $storedUnit->setMaterialTypeName($unit->getMaterialType()->getParentType());
        $storedUnit->setMaterialSubTypeName($unit->getMaterialType()->getSubType());
        $storedUnit->setMaterialNumber($unit->getMaterialNumber());
        $storedUnit->setHasToilet($unit->hasToilet());
        $storedUnit->setHasPrmToilet($unit->hasPrmToilet());
        $storedUnit->setHasAirco($unit->hasAirco());
        $storedUnit->setHasBikeSection($unit->hasBikeSection());
        $storedUnit->setHasPrmSection($unit->hasPrmSection());
        $storedUnit->setSeatsFirstClass($unit->getSeatsFirstClass());
        $storedUnit->setSeatsSecondClass($unit->getSeatsSecondClass());
        $storedUnit->save();
    }

    /**
     * @param Vehicle $vehicle
     * @param Carbon  $journeyStartDate
     * @param string  $fromId
     * @param string  $toId
     * @return int|null
     */
    public function getCompositionId(TrainComposition $composition): ?int
    {
        $first = CompositionHistoryEntry::where([
            'journeyType'      => $composition->getVehicle()->getType(),
            'journeyNumber'    => $composition->getVehicle()->getNumber(),
            'journeyStartDate' => $composition->getVehicle()->getJourneyStartDate(),
            'fromStationId'    => $composition->getOrigin()->getId(),
            'toStationId'      => $composition->getDestination()->getId()
        ])->first();
        return $first ? $first->getId() : null;
    }

    /**
     * @param $lengths
     * @return int
     */
    public function median($lengths): int
    {
        $count = count($lengths);
        if ($count == 0) {
            return 0;
        }
        return $count % 2 == 1 ? $lengths[$count / 2] : ($lengths[($count / 2) - 1] + $lengths[($count / 2)] / 2);
    }
}