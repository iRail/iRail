<?php

namespace Irail\Data\Nmbs\Repositories\Gtfs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Irail\Data\NMBS\Repositories\Gtfs\Models\VehicleWithOriginAndDestination;
use Irail\Data\Nmbs\Tools\Tools;

class GtfsRepository
{
    const GTFS_ROUTES = "https://gtfs.irail.be/nmbs/gtfs/latest/routes.txt";
    const GTFS_STOP_TIMES = "https://gtfs.irail.be/nmbs/gtfs/latest/stop_times.txt";

    /**
     * @return array<String, String[]> Get an array grouping service ids by the date (Ymd format) they are run on.
     */
    public static function readCalendarDates()
    {
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/calendar_dates.txt", "r");
        $serviceIdsByDate = [];

        $SERVICE_ID_COLUMN = 0;
        $DATE_COLUMN = 1;

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            $date = $row[$DATE_COLUMN];
            if (!key_exists($date, $serviceIdsByDate)) {
                $serviceIdsByDate[$date] = [];
            }
            $serviceId = Tools::safeIntVal($row[$SERVICE_ID_COLUMN]);
            $serviceIdsByDate[$date][] = $serviceId;
        }
        return $serviceIdsByDate;
    }

    /**
     * @param array<String> $serviceIdsToRetain service ids for which the data should be retained. If a service id is not in this list, it will be ignored.
     * @return array<String, VehicleWithOriginAndDestination[]> VehicleWithOriginAndDestination objects grouped by their service ids.
     */
    public function readTripsGroupedByServiceId(array $serviceIdsToRetain): array
    {
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/trips.txt", "r");
        $vehicleDetailsByServiceId = [];

        $SERVICE_ID_COLUMN = 1;
        $ROUTE_ID_COLUMN = 0;
        $TRIP_ID_COLUMN = 2;
        $TRIP_SHORT_NAME_COLUMN = 4;

        $vehicleTypeByTripId = $this->readVehicleTypes();

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            $serviceId = Tools::safeIntVal($row[$SERVICE_ID_COLUMN]);
            $tripId = $row[$TRIP_ID_COLUMN];
            $routeId = $row[$ROUTE_ID_COLUMN];

            if (!in_array($serviceId, $serviceIdsToRetain)) {
                continue;
            }

            if (!key_exists($serviceId, $vehicleDetailsByServiceId)) {
                $vehicleDetailsByServiceId[$serviceId] = [];
            }

            $trainNumber = Tools::safeIntVal($row[$TRIP_SHORT_NAME_COLUMN]);
            $tripIdParts = explode(':', $tripId);
            $vehicleType = $vehicleTypeByTripId[$routeId];

            $vehicleDetails = new VehicleWithOriginAndDestination($tripId, $vehicleType, $trainNumber, $tripIdParts[3], $tripIdParts[4]);
            $vehicleDetailsByServiceId[$serviceId][] = $vehicleDetails;
        }

        return $vehicleDetailsByServiceId;
    }

    public function getServiceIdsInDateRange(
        array $serviceIdsByCalendarDate,
        int $daysBack,
        int $daysForward
    ) {
        $date = Carbon::now();
        $serviceIdsToKeep = [];

        $startDate = $date->copy()->subDays(abs($daysBack));
        $endDate = $date->copy()->addDays(abs($daysForward));
        for ($dateToKeep = $startDate; $dateToKeep <= $endDate; $dateToKeep->addDay()) {
            $serviceIdsOnDay = $serviceIdsByCalendarDate[$dateToKeep->format('Ymd')];
            foreach ($serviceIdsOnDay as $serviceIdOnDay) {
                $serviceIdsToKeep[] = $serviceIdOnDay;
            }
        }
        return array_unique($serviceIdsToKeep);
    }

    /**
     * Get an array mapping each trip_id to the stops on that trip.
     * @return array<String, String[]>
     */
    public function readTripStops(): array
    {
        Log::info("reading stop_times");
        $fileStream = fopen(self::GTFS_STOP_TIMES, "r");
        $stopsByTripId = [];

        $TRIP_ID_COLUMN = 0;
        $STOP_ID_COLUMN = 3;
        $STOP_PICKUP_TYPE = 6;
        $STOP_DROPOFF_TYPE = 7;

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            $trip_id = $row[$TRIP_ID_COLUMN];
            if (!key_exists($trip_id, $stopsByTripId)) {
                $stopsByTripId[$trip_id] = [];
            }
            $stopId = $row[$STOP_ID_COLUMN];

            if ($row[$STOP_PICKUP_TYPE] == 1 && $row[$STOP_DROPOFF_TYPE] == 1) {
                // Ignore "stops" where the train only passes, without possibility to embark or disembark.
                continue;
            }

            # Assume all stop_times are in chronological order, we don't have time to sort this.
            $stopsByTripId[$trip_id][] = $stopId;
        }
        Log::info("Read stop_times");
        return $stopsByTripId;
    }

    /**
     * @return array<String,String> An associative array mapping route ids to vehicle types.
     */
    private function readVehicleTypes(): array
    {
        $fileStream = fopen(self::GTFS_ROUTES, "r");
        $vehicleTypesByRouteId = [];

        $ROUTE_ID_COLUMN = 0;
        $VEHICLE_TYPE_COLUMN = 2; // Vehicle type is stored as route short name

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            $routeId = $row[$ROUTE_ID_COLUMN];
            $vehicleType = $row[$VEHICLE_TYPE_COLUMN];
            $vehicleTypesByRouteId[$routeId] = $vehicleType;
        }

        return $vehicleTypesByRouteId;
    }
}