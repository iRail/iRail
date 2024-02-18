<?php

namespace Irail\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Irail\Models\Dao\CompositionHistoryEntry;
use Irail\Models\Dao\CompositionStatistics;
use Irail\Models\Dao\StoredComposition;
use Irail\Models\Dao\StoredCompositionUnit;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use Irail\Models\VehicleComposition\TrainCompositionUnitWithId;
use stdClass;

class HistoricCompositionDao
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
        return StoredCompositionUnit::all()->orderBy('uic_code', 'desc')->all();
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
                ['journey_type', '=', $vehicleType],
                ['journey_number', '=', $journeyNumber],
                ['journey_start_date', '>=', Carbon::now()->startOfDay()->subDays($daysBack)]
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
        $cacheKey = $this->getStatisticsCacheKey($vehicleType, $journeyNumber, $daysBack);
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue) {
            return $cachedValue;
        }

        $compositions = $this->getHistoricCompositions(
            $vehicleType,
            $journeyNumber,
            $daysBack);
        $lengths = array_map(fn($comp) => $comp->getPassengerUnitCount(), $compositions);
        sort($lengths);
        $lengthFrequency = array_count_values($lengths);
        $typesFrequency = array_count_values(array_map(fn($comp) => $comp->getPrimaryMaterialType(), $compositions));
        $medianLength = $this->median($lengths);
        $mostProbableLength = $lengthFrequency ? array_keys($lengthFrequency, max($lengthFrequency))[0] : 0;
        $lengthPercentage = $lengthFrequency ? 100 * max($lengthFrequency) / count($compositions) : 0;
        $primaryMaterialType = $typesFrequency ? array_keys($typesFrequency, max($typesFrequency))[0] : null;
        $typePercentage = $lengthFrequency ? 100 * max($lengthFrequency) / count($compositions) : 0;
        $result = [
            new CompositionStatistics(count($compositions), $medianLength, $mostProbableLength, $lengthPercentage,
                $primaryMaterialType, $typePercentage)
        ];
        Cache::put($cacheKey, $result, 3600 * 12); // Cache for 12 hours
        return $result;
    }

    /**
     * Get the complete historic composition for a journey on a given day.
     *
     * @return StoredComposition[]
     */
    public function getHistoricComposition(Vehicle $vehicle): array
    {
        $rows = DB::select('SELECT CU.*, CH.from_station_id, CH.to_station_id, CUU.position 
            FROM composition_history CH  
            JOIN composition_unit_usage CUU on CH.id = CUU.historic_composition_id
            JOIN composition_unit CU on CU.uic_code = CUU.uic_code
            WHERE CH.journey_type = ? AND CH.journey_number = ? AND CH.journey_start_date = ? 
            ORDER BY CH.from_station_id, CUU.position',
            [$vehicle->getType(), $vehicle->getNumber(), $vehicle->getJourneyStartDate()]);
        if (count($rows) == 0) {
            return [];
        }

        $compositionsBySegment = [];
        foreach ($rows as $row) {
            $unit = $this->transformCompositionUnit($row);
            $fromStationId = $row->from_station_id;
            $toStationId = $row->to_station_id;
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

        if ($this->hasBeenRecordedPast12Hours($composition)) {
            // Try to exit without any database queries to combine rich data with fast response times
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
            if (!($units instanceof TrainCompositionUnitWithId)) {
                // TODO: When reading these compositions back, the difference between the summary and stored data should be detected
                Log::debug("Cannot record historic composition due to missing material ids for journey {$composition->getVehicle()->getId()} reported by {$composition->getCompositionSource()}");
                continue;
            }
            $this->insertIfNotExists($unit);
            DB::update('INSERT INTO composition_unit_usage(uic_code, historic_composition_id, position) VALUES (?,?,?)', [
                $unit->getUicCode(),
                $compositionId,
                $position + 1
            ]);
        }
        $this->markAsRecorded($composition);
    }


    private function transformCompositionUnit(StdClass $row): StoredCompositionUnit
    {
        return (new StoredCompositionUnit())
            ->setUicCode($row->uic_code)
            ->setMaterialTypeName($row->material_type_name)
            ->setMaterialSubTypeName($row->material_subtype_name)
            ->setMaterialNumber($row->material_number)
            ->setHasToilet($row->has_toilet)
            ->setHasPrmToilet($row->has_prm_toilet)
            ->setHasAirco($row->has_airco)
            ->setHasBikeSection($row->has_bike_section)
            ->setHasPrmSection($row->has_prm_section)
            ->setSeatsFirstClass($row->seats_first_class)
            ->setSeatsSecondClass($row->seats_second_class)
            ->setCreatedAt(Carbon::parse($row->created_at))
            ->setUpdatedAt(Carbon::parse($row->updated_at));
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
     * @param TrainComposition $composition
     * @return int|null
     */
    public function getCompositionId(TrainComposition $composition): ?int
    {
        $first = CompositionHistoryEntry::where([
            'journey_type'       => $composition->getVehicle()->getType(),
            'journey_number'     => $composition->getVehicle()->getNumber(),
            'journey_start_date' => $composition->getVehicle()->getJourneyStartDate(),
            'from_station_id'    => $composition->getOrigin()->getId(),
            'to_station_id'      => $composition->getDestination()->getId()
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

    private function hasBeenRecordedPast12Hours(TrainComposition|VehicleCompositionSearchResult $composition): bool
    {
        return Cache::has($this->getRecordedStatusCacheKey($composition));
    }

    private function markAsRecorded(TrainComposition|VehicleCompositionSearchResult $composition): bool
    {
        return Cache::set($this->getRecordedStatusCacheKey($composition), true, 12 * 3600);
    }

    /**
     * @param TrainComposition $composition
     * @return string
     */
    public function getRecordedStatusCacheKey(TrainComposition $composition): string
    {
        return 'historicCompositionRecorded:'
            . ":{$composition->getVehicle()->getId()}"
            . ":{$composition->getOrigin()->getId()}:{$composition->getDestination()->getId()}";
    }

    public function getStatisticsCacheKey(string $vehicleType, int $journeyNumber, int $daysBack): string
    {
        return "statistics:$vehicleType$journeyNumber:$daysBack";
    }
}