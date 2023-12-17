<?php

namespace Irail\Repositories\Irail;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Irail\Models\Dao\OccupancyReportSource;
use Irail\Models\DepartureOrArrival;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Spatie\Async\Pool;

class OccupancyRepository
{

    private Pool $threadPool;

    public function __construct()
    {
        $this->threadPool = Pool::create();
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param DepartureOrArrival  $departure
     * @param OccupancyLevel|null $officialNmbsLevel The official NMBS level, if known. If not known, iRail will attempt to read this from previous response data.
     * @return OccupancyInfo
     */
    public function getOccupancy(DepartureOrArrival $departure, ?OccupancyLevel $officialNmbsLevel = null): OccupancyInfo
    {
        if ($officialNmbsLevel != null && $officialNmbsLevel != OccupancyLevel::UNKNOWN) {
            // TODO: perform this operation asynchronous
            $this->store(OccupancyReportSource::NMBS,
                $departure->getVehicle()->getId(),
                $departure->getStation()->getId(),
                $departure->getScheduledDateTime(),
                $officialNmbsLevel);
        } else {
            $officialNmbsLevel = $this->getOfficialLevel(
                $departure->getVehicle()->getId(),
                $departure->getStation()->getId(),
                $departure->getScheduledDateTime()
            );
        }
        $spitsgidsLevel = $this->getSpitsgidsLevel(
            $departure->getVehicle()->getId(),
            $departure->getStation()->getId(),
            $departure->getScheduledDateTime()
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


    private function store(OccupancyReportSource $source, string $vehicleId, int $stationId, Carbon $vehicleJourneyStartDate, OccupancyLevel $occupancyLevel): void
    {
        if ($source == OccupancyReportSource::NMBS && count($this->readLevels($source, $vehicleId, $stationId, $vehicleJourneyStartDate)) > 0) {
            return;
        }

        DB::update('INSERT INTO OccupancyReports (source, vehicleId, stopId, journeyStartDate, occupancy) VALUES (?, ?, ?, ?, ?)', [
            $source->value,
            $vehicleId,
            $stationId,
            $vehicleJourneyStartDate->copy()->startOfDay(),
            $occupancyLevel->getIntValue()
        ]);
    }

    private function getSpitsgidsLevel(string $vehicleId, string $stationId, Carbon $scheduledDateTime): OccupancyLevel
    {
        $reports = $this->readLevels(
            OccupancyReportSource::SPITSGIDS,
            $vehicleId,
            $stationId,
            $scheduledDateTime
        );

        // the reported values cannot include "unknown", since this value can never be reported in Spitsgids
        $values = array_map(fn($report) => $report->getIntValue(), $reports);
        if (count($values) == 0) {
            return OccupancyLevel::UNKNOWN;
        }
        $average = array_sum($values) / count($values);
        return OccupancyLevel::fromIntValue($average);
    }

    private function getOfficialLevel(string $vehicleId, string $stationId, Carbon $scheduledDateTime): OccupancyLevel
    {
        $reports = $this->readLevels(
            OccupancyReportSource::NMBS,
            $vehicleId,
            $stationId,
            $scheduledDateTime
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
     * @param OccupancyReportSource $source The source for which to read reports
     * @param string                $vehicleId The vehicle for which to read reports.
     * @param int                   $stationId The station for which to read reports.
     * @param DateTime              $vehicleJourneyStartDate The vehicle journey start date for which to read reports.
     * @return OccupancyLevel[] The reports which have been found.
     */
    private function readLevels(OccupancyReportSource $source, string $vehicleId, int $stationId, Carbon $vehicleJourneyStartDate): array
    {
        $rows = DB::select('SELECT occupancy FROM OccupancyReports WHERE source=? AND vehicleId=? AND stopId=? AND journeyStartDate=?',
            [
                $source->value,
                $vehicleId,
                $stationId,
                $vehicleJourneyStartDate->copy()->startOfDay()
            ]);
        return array_map(fn($row) => OccupancyLevel::fromIntValue($row->occupancy), $rows);
    }

    private function exportSpitsgidsReport(Carbon $reportDate): array
    {
        $rows = DB::select('SELECT occupancy FROM OccupancyReports WHERE source=? AND DATE(createdAt)=? ',
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
}