<?php

namespace Irail\api\data\NMBS\tools;

use Carbon\Carbon;
use Closure;
use Exception;
use Irail\api\data\models\hafas\VehicleWithOriginAndDestination;

class GtfsTripStartEndExtractor
{
    const TRIP_STOPS_CACHE_KEY = "gtfs|stopsByTrip";
    const GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY = "gtfs|vehicleDetailsByDate";

    /**
     * @param string $vehicleId The vehicle name/id, such as IC538
     * @param string $date The date in YYYYmmdd format
     * @return false|VehicleWithOriginAndDestination
     * @throws Exception
     */
    public static function getVehicleWithOriginAndDestination(string $vehicleId, string $date): VehicleWithOriginAndDestination|false
    {
        $vehicleNumber = Tools::safeIntVal(VehicleIdTools::extractTrainNumber($vehicleId));
        $vehicleDetailsForDate = self::getTripsWithStartAndEndByDate($date);
        $foundVehicleWithInternationalOriginAndDestination = false;

        foreach ($vehicleDetailsForDate as $vehicleWithOriginAndDestination) {
            if ($vehicleWithOriginAndDestination->getVehicleNumber() == $vehicleNumber) {
                // International journeys are split into two parts at tha Belgian side of the border, where the
                // border is represented by a "station" with a belgian ID.
                // If the journey is between belgian stops, return immediatly
                if (str_starts_with($vehicleWithOriginAndDestination->getOriginStopId(), '88')
                    && str_starts_with($vehicleWithOriginAndDestination->getDestinationStopId(), '88')) {
                    return $vehicleWithOriginAndDestination;
                }
                // Otherwise, keep the "international" stretch as a last-change backup should we not find a belgian part.
                $foundVehicleWithInternationalOriginAndDestination = $vehicleWithOriginAndDestination;
            }
        }
        if ($foundVehicleWithInternationalOriginAndDestination) {
            return $foundVehicleWithInternationalOriginAndDestination;
        }
        return false;
    }


    /**
     * Get alternative origins and destinations for a vehicle, in case one of the first/last stops is cancelled.
     *
     * @param VehicleWithOriginAndDestination $originalVehicle
     * @return VehicleWithOriginAndDestination[]
     * @throws Exception
     */
    public static function getAlternativeVehicleWithOriginAndDestination(VehicleWithOriginAndDestination $originalVehicle): array
    {
        # error_log("getAlternativeVehicleWithOriginAndDestination called for trip $tripId");
        $stops = self::getStopsForTrip($originalVehicle->getTripId());
        $results = [];
        for ($i = 1; $i < count($stops); $i++) {
            $results[] = new VehicleWithOriginAndDestination(
                $originalVehicle->getTripId(),
                $originalVehicle->getVehicleType(),
                $originalVehicle->getVehicleNumber(),
                $stops[$i - 1],
                $stops[$i]
            );
        }
        # error_log("getAlternativeVehicleWithOriginAndDestination returned " . count($results) . " results for trip $tripId");
        return $results;
    }

    /**
     * @return VehicleWithOriginAndDestination[]
     * @throws Exception
     */
    private static function getTripsWithStartAndEndByDate(string $tripStartDate): array
    {
        // Check the cache here to prevent going in the synchronized method
        $vehicleDetailsByDate = Tools::getCachedObject(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY);
        if ($vehicleDetailsByDate === false) {
            // Synchronized method to prevent multiple requests from filling the cache with compute-intensive data
            self::synchronized(fn() => self::loadTripsWithStartAndEndDateInCache());
            $vehicleDetailsByDate = Tools::getCachedObject(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY);
        }
        if (!key_exists($tripStartDate, $vehicleDetailsByDate)) {
            throw new Exception("Request outside of allowed date period (3 days back, 10 days forward)", 404);
        }
        return $vehicleDetailsByDate[$tripStartDate];
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private static function getStopsForTrip(string $tripId): array
    {
        // Check the cache here to prevent going in the synchronized method
        $tripStops = Tools::getCachedObject(self::TRIP_STOPS_CACHE_KEY);
        if ($tripStops === false) {
            // Synchronized method to prevent multiple requests from filling the cache with compute-intensive data
            self::synchronized(fn() => self::readTripStops());
            $tripStops = self::readTripStops();
        }
        if (!key_exists($tripId, $tripStops)) {
            throw new Exception("Trip not found", 404);
        }
        return $tripStops[$tripId];
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function loadTripsWithStartAndEndDateInCache(): array
    {
        $vehicleDetailsByDate = Tools::getCachedObject(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY);
        if ($vehicleDetailsByDate !== false) {
            return $vehicleDetailsByDate;
        }

        $serviceIdsByCalendarDate = self::readCalendarDates();
        // Only keep service ids in a specific date range (x days back, y days forward), to keep cpu/ram usage down
        $serviceIdsToRetain = self::getServiceIdsInDateRange($serviceIdsByCalendarDate, 3, 10);
        $vehicleDetailsByServiceId = self::readTrips($serviceIdsToRetain);

        if (empty($serviceIdsByCalendarDate) || empty($vehicleDetailsByServiceId)) {
            throw new Exception("No response from iRail GTFS", 504);
        }

        // Create a multidimensional array:
        // date (yyyymmdd) => VehicleWithRoute[]
        $vehicleDetailsByDate = [];
        foreach ($serviceIdsByCalendarDate as $date => $serviceIds) {
            $vehicleDetailsByDate[$date] = [];
            foreach ($serviceIds as $serviceId) {
                if (key_exists($serviceId, $vehicleDetailsByServiceId)) {
                    foreach ($vehicleDetailsByServiceId[$serviceId] as $vehicleDetails) {
                        $vehicleDetailsByDate[$date][] = $vehicleDetails;
                    }
                }
            }
        }

        Tools::setCachedObject(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY, $vehicleDetailsByDate, 7200);
        return $vehicleDetailsByDate;
    }

    private static function readCalendarDates()
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
        fclose($fileStream);
        return $serviceIdsByDate;
    }

    /**
     * @return VehicleWithOriginAndDestination[]
     */
    private static function readTrips(array $serviceIdsToRetain): array
    {
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/trips.txt", "r");
        $vehicleDetailsByServiceId = [];

        $SERVICE_ID_COLUMN = 1;
        $ROUTE_ID_COLUMN = 0;
        $TRIP_ID_COLUMN = 2;
        $TRIP_SHORT_NAME_COLUMN = 4;

        $vehicleTypeByTripId = self::readVehicleTypes();

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
        fclose($fileStream);
        return $vehicleDetailsByServiceId;
    }

    private static function getServiceIdsInDateRange(
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

    private static function readTripStops(): array
    {
        $stopsByTripId = Tools::getCachedObject(self::TRIP_STOPS_CACHE_KEY);
        if ($stopsByTripId !== false) {
            return $stopsByTripId;
        }
        error_log("Reading stop_times... ");
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/stop_times.txt", "r");
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
        fclose($fileStream);
        error_log("read stop_times");
        # This takes a long time to generate, and should be cached quite long
        Tools::setCachedObject(self::TRIP_STOPS_CACHE_KEY, $stopsByTripId, 14400);
        return $stopsByTripId;
    }

    /**
     * @return array An associative array mapping route ids to vehicle types.
     */
    private static function readVehicleTypes(): array
    {
        $fileStream = fopen("https://gtfs.irail.be/nmbs/gtfs/latest/routes.txt", "r");
        $vehicleTypesByRouteId = [];

        $ROUTE_ID_COLUMN = 0;
        $VEHICLE_TYPE_COLUMN = 2; // Vehicle type is stored as route short name

        $headers = fgetcsv($fileStream); // ignore the headers
        while ($row = fgetcsv($fileStream)) {
            $routeId = $row[$ROUTE_ID_COLUMN];
            $vehicleType = $row[$VEHICLE_TYPE_COLUMN];
            $vehicleTypesByRouteId[$routeId] = $vehicleType;
        }
        fclose($fileStream);
        return $vehicleTypesByRouteId;
    }

    /**
     * Execute a callback method, while ensuring this method is only being executed by one thread at a time.
     * This prevents multiple requests racing to fill a cache, which would put an unnecessary strain on the server.
     * @param $callback Closure The function to execute
     * @return mixed The function return value
     * @throws Exception
     */
    static function synchronized(Closure $callback)
    {
        $lockName = 'lock|' . md5(json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]));
        if (Tools::getCachedObject($lockName) === true) {
            // Since loading the data can take up to 60s, other requests should not be blocking server resources
            throw new Exception('GTFS data is being processed. Please try again later.', 503);
        }
        error_log("Locking $lockName");
        Tools::setCachedObject($lockName, true, 60); // In case the callback method does not complete, the lock will still release after 60s
        $result = $callback();
        error_log("Freeing $lockName");
        Tools::setCachedObject($lockName, false);
        return $result;
    }
}
