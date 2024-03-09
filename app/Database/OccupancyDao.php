<?php

namespace Irail\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Irail\Models\Dao\OccupancyReportSource;
use Irail\Models\DepartureOrArrival;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Spatie\Async\Pool;

class OccupancyDao
{

    private Pool $threadPool;

    public function __construct()
    {
        $this->threadPool = Pool::create();
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param DepartureOrArrival          $departure
     * @param OccupancyLevel|null         $officialNmbsLevel The official NMBS level, if known. If not known, iRail will attempt to read this from previous response data.
     * @param OccupancyDaoPerformanceMode $performanceMode How the performance of database queries should be optimized.
     *                                                     Use VEHICLE when many queries for the same vehicle will be made.
     *                                                     Use station when many queries for the same station will be made.
     * @return OccupancyInfo
     */
    public function getOccupancy(
        DepartureOrArrival $departure,
        ?OccupancyLevel $officialNmbsLevel = null,
        OccupancyDaoPerformanceMode $performanceMode = OccupancyDaoPerformanceMode::VEHICLE
    ): OccupancyInfo
    {
        if ($officialNmbsLevel != null && $officialNmbsLevel != OccupancyLevel::UNKNOWN) {
            // TODO: perform this operation asynchronous
            $this->store(OccupancyReportSource::NMBS,
                $departure->getVehicle()->getId(),
                $departure->getStation()->getId(),
                $departure->getScheduledDateTime(),
                $officialNmbsLevel);

        } else {
            $officialNmbsLevel = $this->getStoredNmbsOccupancy($departure, $performanceMode);
        }
        $spitsgidsLevel = $this->getSpitsgidsLevel(
            $departure->getVehicle()->getId(),
            $departure->getStation()->getId(),
            $departure->getScheduledDateTime(),
            $performanceMode
        );
        return new OccupancyInfo($officialNmbsLevel ?: OccupancyLevel::UNKNOWN, $spitsgidsLevel);
    }

    /**
     * Record a spitsgids occupancy record
     * @param string         $vehicleId
     * @param int            $stationId
     * @param Carbon         $vehicleJourneyStartDate
     * @param OccupancyLevel $occupancyLevel
     * @return OccupancyInfo The updated occupancy.
     */
    public function recordSpitsgidsOccupancy(string $vehicleId, int $stationId, Carbon $vehicleJourneyStartDate, OccupancyLevel $occupancyLevel): OccupancyInfo
    {
        $this->store(OccupancyReportSource::SPITSGIDS, $vehicleId, $stationId, $vehicleJourneyStartDate, $occupancyLevel);
        $officialNmbsLevel = $this->getOfficialLevel(
            $vehicleId,
            $stationId,
            $vehicleJourneyStartDate
        );
        $spitsgidsLevel = $this->getSpitsgidsLevel(
            $vehicleId,
            $stationId,
            $vehicleJourneyStartDate
        );
        return new OccupancyInfo($officialNmbsLevel, $spitsgidsLevel);
    }


    private function store(
        OccupancyReportSource $source,
        string $vehicleId,
        int $stationId,
        Carbon $vehicleJourneyStartDate,
        OccupancyLevel $occupancyLevel
    ): void {
        $recordedFlagKey = $this->getNmbsRecordedKey($vehicleId, $stationId, $vehicleJourneyStartDate);
        if ($source == OccupancyReportSource::NMBS) {
            // No need to store if this has been stored in the database already.
            // This cache check prevents unneeded database access where possible
            if (Cache::has($recordedFlagKey)) {
                return;
            }
        }

        // Check if this entry is already recorded in the database, since NMBS entries only should be recorded once
        if ($source == OccupancyReportSource::NMBS && count($this->readLevels($source, $vehicleId, $stationId, $vehicleJourneyStartDate, VEHICLE)) > 0) {
            Cache::put($recordedFlagKey, true, 12 * 3600);
            return;
        }

        Log::debug("Storing occupancy level $occupancyLevel->name for $vehicleId from source $source->name");
        DB::update('INSERT INTO occupancy_reports (source, vehicle_id, stop_id, journey_start_date, occupancy) VALUES (?, ?, ?, ?, ?)', [
            $source->value,
            $vehicleId,
            $stationId,
            $vehicleJourneyStartDate->copy()->startOfDay(),
            $occupancyLevel->getIntValue()
        ]);
        Cache::put($recordedFlagKey, true, 12 * 3600);

        Log::debug("Clearing occupancy cache for vehicle $vehicleId, station $stationId, source {$source->name}");
        $cacheKey = $this->getOccupancyKey($source, $vehicleId, $stationId, $vehicleJourneyStartDate); // Clear cache at station level
        Cache::delete($cacheKey); // Clear cached value after store
        $cacheKey = $this->getOccupancyKey($source, $vehicleId, null, $vehicleJourneyStartDate); // Clear cache at vehicle level as well
        Cache::delete($cacheKey); // Clear cached value after store
        $cacheKey = $this->getOccupancyKey($source, null, $stationId, $vehicleJourneyStartDate); // Clear cache at station level as well
        Cache::delete($cacheKey); // Clear cached value after store
    }

    private function getSpitsgidsLevel(
        string $vehicleId,
        string $stationId,
        Carbon $scheduledDateTime,
        OccupancyDaoPerformanceMode $performanceMode = OccupancyDaoPerformanceMode::VEHICLE
    ): OccupancyLevel
    {
        $cacheKey = $this->getOccupancyKey(OccupancyReportSource::SPITSGIDS, $vehicleId, $stationId, $scheduledDateTime);
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue != null) {
            return $cachedValue;
        }

        $reports = $this->readLevels(
            OccupancyReportSource::SPITSGIDS,
            $vehicleId,
            $stationId,
            $scheduledDateTime,
            $performanceMode
        );

        // the reported values cannot include "unknown", since this value can never be reported in Spitsgids
        $values = array_map(fn($report) => $report->getIntValue(), $reports);
        if (count($values) == 0) {
            Cache::put($cacheKey, OccupancyLevel::UNKNOWN, 1800);
            return OccupancyLevel::UNKNOWN;
        }
        $average = array_sum($values) / count($values);
        $occupancyLevel = OccupancyLevel::fromIntValue($average);
        Cache::put($cacheKey, $occupancyLevel, 3 * 3600);
        return $occupancyLevel;
    }

    /**
     * @param DepartureOrArrival          $departure
     * @param OccupancyDaoPerformanceMode $performanceMode
     * @return OccupancyLevel
     */
    public function getStoredNmbsOccupancy(
        DepartureOrArrival $departure,
        OccupancyDaoPerformanceMode $performanceMode = OccupancyDaoPerformanceMode::VEHICLE
    ): OccupancyLevel
    {
        $cacheKey = $this->getOccupancyKey(OccupancyReportSource::NMBS, $departure->getVehicle()->getId(),
            $departure->getStation()->getId(), $departure->getScheduledDateTime());
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue != null) {
            $officialNmbsLevel = $cachedValue;
        } else {
            $officialNmbsLevel = $this->getOfficialLevel(
                $departure->getVehicle()->getId(),
                $departure->getStation()->getId(),
                $departure->getScheduledDateTime(),
                $performanceMode
            );
            Cache::put($cacheKey, $officialNmbsLevel, 3 * 3600);
        }
        return $officialNmbsLevel;
    }

    private function getOfficialLevel(
        string $vehicleId,
        string $stationId,
        Carbon $scheduledDateTime,
        OccupancyDaoPerformanceMode $performanceMode = OccupancyDaoPerformanceMode::VEHICLE
    ): OccupancyLevel
    {
        $reports = $this->readLevels(
            OccupancyReportSource::NMBS,
            $vehicleId,
            $stationId,
            $scheduledDateTime,
            $performanceMode
        );
        if (count($reports) == 0) {
            return OccupancyLevel::UNKNOWN;
        }
        return $reports[0];
    }

    public function getReports(Carbon $date): array
    {
        return $this->exportSpitsgidsReport($date);
    }

    /**
     * Read all stored occupancy levels from the database.
     *
     * @param OccupancyReportSource       $source The source for which to read reports
     * @param string                      $vehicleId The vehicle for which to read reports.
     * @param int                         $stationId The station for which to read reports.
     * @param Carbon                      $vehicleJourneyStartDate The vehicle journey start date for which to read reports.
     * @param OccupancyDaoPerformanceMode $performanceMode
     * @return OccupancyLevel[] The reports which have been found.
     */
    private function readLevels(
        OccupancyReportSource $source,
        string $vehicleId,
        int $stationId,
        Carbon $vehicleJourneyStartDate,
        OccupancyDaoPerformanceMode $performanceMode = OccupancyDaoPerformanceMode::VEHICLE
    ): array
    {
        if ($performanceMode == OccupancyDaoPerformanceMode::VEHICLE) {
            $levels = $this->readLevelsForVehicle($source, $vehicleId, $vehicleJourneyStartDate);
        } else {
            $levels = $this->readLevelsForStation($source, $stationId, $vehicleJourneyStartDate);
        }
        return key_exists($stationId, $levels) ? $levels[$stationId] : [];
    }

    /**
     * Read all stored occupancy levels from the database for a given vehicle at a given date. Reducing the number of database round trips improves performance.
     *
     * @param OccupancyReportSource $source The source for which to read reports
     * @param string                $vehicleId The vehicle for which to read reports.
     * @param Carbon                $vehicleJourneyStartDate The vehicle journey start date for which to read reports.
     * @return Array<String, OccupancyLevel[]> The reports which have been found for each station.
     */
    private function readLevelsForVehicle(OccupancyReportSource $source, string $vehicleId, Carbon $vehicleJourneyStartDate): array
    {
        $cacheKey = $this->getOccupancyKey($source, $vehicleId, null, $vehicleJourneyStartDate); // Cache at vehicle level
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue !== null) {
            return $cachedValue;
        }

        Log::debug("Reading occupancy levels for vehicle $vehicleId from source $source->name");
        $startTime = microtime(true);

        $rows = DB::select('SELECT stop_id, occupancy FROM occupancy_reports WHERE source=? AND vehicle_id=? AND journey_start_date=?',
            [
                $source->value,
                $vehicleId,
                $vehicleJourneyStartDate->copy()->startOfDay()
            ]);
        // Convert the results
        $result = [];
        foreach ($rows as $row) {
            if (!key_exists($row->stop_id, $result)) {
                $result[$row->stop_id] = [];
            }
            $result[$row->stop_id][] = OccupancyLevel::fromIntValue($row->occupancy);
        }

        Cache::put($cacheKey, $result, 3 * 3600);
        $duration = floor((microtime(true) - $startTime) * 1000);
        Log::debug("Queried occupancy levels for {$source->name}, vehicle $vehicleId in $duration ms, results: " . count($result));
        return $result;
    }

    /**
     * Read all stored occupancy levels from the database for a given station at a given date. Reducing the number of database round trips improves performance.
     *
     * @param OccupancyReportSource $source The source for which to read reports
     * @param int                   $stationId The station for which to read reports.
     * @param Carbon                $vehicleJourneyStartDate The vehicle journey start date for which to read reports.
     * @return Array<String, OccupancyLevel[]> The reports which have been found for each station.
     */
    private function readLevelsForStation(OccupancyReportSource $source, int $stationId, Carbon $vehicleJourneyStartDate): array
    {
        $cacheKey = $this->getOccupancyKey($source, null, $stationId, $vehicleJourneyStartDate); // Cache at station level
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue !== null) {
            return $cachedValue;
        }

        Log::debug("Reading occupancy levels for stop $stationId from source $source->name");
        $startTime = microtime(true);

        $rows = DB::select('SELECT vehicle_id, occupancy FROM occupancy_reports WHERE source=? AND stop_id=? AND journey_start_date=?',
            [
                $source->value,
                $stationId,
                $vehicleJourneyStartDate->copy()->startOfDay()
            ]);
        // Convert the results
        $result = [];
        foreach ($rows as $row) {
            if (!key_exists($row->vehicle_id, $result)) {
                $result[$row->vehicle_id] = [];
            }
            $result[$row->vehicle_id][] = OccupancyLevel::fromIntValue($row->occupancy);
        }

        Cache::put($cacheKey, $result, 3 * 3600);
        $duration = floor((microtime(true) - $startTime) * 1000);
        Log::debug("Queried occupancy levels for {$source->name}, stop $stationId in $duration ms, results: " . count($result));
        return $result;
    }

    private function exportSpitsgidsReport(Carbon $reportDate): array
    {
        $rows = DB::select('SELECT occupancy FROM occupancy_reports WHERE source=? AND DATE(created_at)=? ',
            [
                OccupancyReportSource::SPITSGIDS,
                $reportDate
            ]);

        return array_map(fn($row) => [
            'connection' => "http=>//irail.be/connections/{$row->stationId}/{$row->date}/{$row->vehicleId}",
            'from'       => 'http=>//irail.be/stations/NMBS/00' . $row->stationId,
            'date'       => $row->date,
            'vehicle'    => 'http=>//irail.be/vehicle/' . $row->vehicleId,
            'occupancy'  => OccupancyLevel::fromIntValue($row->occupancy)
        ], $rows);
    }

    private function getOccupancyKey(OccupancyReportSource $source, ?string $vehicleId, ?string $stationId, Carbon $date): string
    {
        return "occupancy:{$source->value}:$vehicleId:$stationId:{$date->copy()->startOfDay()->toDateString()}";
    }

    private function getNmbsRecordedKey(string $vehicleId, string $stationId, Carbon $date): string
    {
        return "occupancyRecorded:$vehicleId:$stationId:{$date->copy()->startOfDay()->toDateString()}";
    }
}