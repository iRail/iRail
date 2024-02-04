<?php

namespace Irail\Repositories\Gtfs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Gtfs\Models\Route;
use Irail\Repositories\Gtfs\Models\StopTime;
use Irail\Repositories\Gtfs\Models\Trip;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Traits\Cache;

class GtfsRepository
{
    use Cache;

    const string GTFS_ROUTES_URL = 'https://gtfs.irail.be/nmbs/gtfs/latest/routes.txt';
    const string GTFS_STOP_TIMES_URL = 'https://gtfs.irail.be/nmbs/gtfs/latest/stop_times.txt';
    const string CALENDAR_DATES_URL = 'https://gtfs.irail.be/nmbs/gtfs/latest/calendar_dates.txt';
    const string TRIPS_URL = 'https://gtfs.irail.be/nmbs/gtfs/latest/trips.txt';

    const string GTFS_ALL_TRIP_STOPS_CACHE_KEY = 'stopsByTrip';
    const string GTFS_ALL_CALENDAR_DATES = 'calendarDates';
    const string GTFS_ALL_TRIPS = 'trips';
    const int TRIPS_DAYS_BACK = 3;
    const int TRIPS_DAYS_FORWARD = 14;

    /**
     * @return array<String, JourneyWithOriginAndDestination[]> VehicleWithOriginAndDestination objects grouped by their service ids.
     */
    public function getTripsByJourneyNumberAndStartDate(): array
    {
        $cachedData = $this->getCacheWithDefaultCacheUpdate(self::GTFS_ALL_TRIPS, function (): array {
            return $this->readTripsByJourneyNumberAndStartDate();
        }, ttl: 3 * 3600 + 4); // The additional minutes reduces the risk that both the stops cache and the trips cache expire at the same request.
        return $cachedData->getValue();
    }

    /**
     * Get an array mapping each trip_id to the stops on that trip.
     * @return array<String, StopTime[]>
     */
    public function getTripStops(): array
    {
        $cachedData = $this->getCacheWithDefaultCacheUpdate(self::GTFS_ALL_TRIP_STOPS_CACHE_KEY, function (): array {
            return $this->readTripStops();
        },
            ttl: 3 * 3600 + 1); // The additional minutes reduces the risk that both the stops cache and the trips cache expire at the same request.
        return $cachedData->getValue();
    }

    /**
     * @return array<String,String> An associative array mapping route ids to vehicle types.
     */
    private function getRouteIdToJourneyTypeMap(): array
    {
        $cachedData = $this->getCacheWithDefaultCacheUpdate(self::GTFS_ALL_TRIP_STOPS_CACHE_KEY, function (): array {
            $vehicleTypesByRouteId = [];
            foreach ($this->readRoutes() as $route) {
                // Shortname contains journey type, such as S6, for NMBS.
                $vehicleTypesByRouteId[$route->getRouteId()] = $route->getRouteShortName();
            }
            return $vehicleTypesByRouteId;
        },
            ttl: 3 * 3600 + 7); // The additional minutes reduces the risk that both the stops cache and the trips cache expire at the same request.
        return $cachedData->getValue();
    }

    /**
     * Read all trips.
     *
     * @return Array<String, Array<String, Trip>> Trips by their journey number and start date (Ymd format)
     */
    private function readTripsByJourneyNumberAndStartDate(): array
    {
        $trips = [];
        $serviceIdsToRetain = $this->getServiceIdsInDateRange(self::TRIPS_DAYS_BACK, self::TRIPS_DAYS_FORWARD);
        $vehicleTypeByRouteId = $this->getRouteIdToJourneyTypeMap();

        $fileStream = fopen(self::TRIPS_URL, 'r');

        $headers = fgetcsv($fileStream);
        $SERVICE_ID_COLUMN = array_search('service_id', $headers);
        $ROUTE_ID_COLUMN = array_search('route_id', $headers);
        $TRIP_ID_COLUMN = array_search('trip_id', $headers);

        while ($row = fgetcsv($fileStream)) {
            $serviceId = $row[$SERVICE_ID_COLUMN];
            if (!key_exists($serviceId, $serviceIdsToRetain)) {
                continue;
            }
            $activeDatesYmd = $serviceIdsToRetain[$serviceId];

            $tripId = $row[$TRIP_ID_COLUMN];
            $tripIdParts = explode(':', $tripId);
            $journeyNumber = $tripIdParts[3];

            if (!key_exists($journeyNumber, $trips)) {
                $trips[$journeyNumber] = []; // Initialize new array
            }

            $routeId = $row[$ROUTE_ID_COLUMN];
            $journeyType = $vehicleTypeByRouteId[$routeId];

            $trip = new Trip($tripId, $journeyNumber, $journeyType);
            foreach ($activeDatesYmd as $activeDateYmd) {
                // A journey number might have different services (trips) on different dates, especially when the GTFS data covers multiple timetable periods
                $trips[$journeyNumber][$activeDateYmd] = $trip;
            }
        }
        fclose($fileStream);
        return $trips;
    }

    /**
     * Filter service ids to only keep those which are active in a range from around todays date.
     *
     * @param int $daysBack How many additional days in the past should be kept, in addition to today.
     * @param int $daysForward The number of days in the future to keep, in addition to today.
     * @return array<String, String[]> all service ids active in this date range, along with the dates they are active on.
     */
    private function getServiceIdsInDateRange(
        int $daysBack,
        int $daysForward
    ): array
    {
        $serviceIdsByCalendarDate = $this->getCalendarDates();

        $date = Carbon::now();
        $serviceIdsToKeep = [];

        $startDate = $date->copy()->subDays(abs($daysBack));
        $endDate = $date->copy()->addDays(abs($daysForward));
        for ($dateToKeep = $startDate->copy(); $dateToKeep <= $endDate; $dateToKeep->addDay()) {
            $serviceIdsOnDay = $serviceIdsByCalendarDate[$dateToKeep->format('Ymd')];
            foreach ($serviceIdsOnDay as $serviceIdOnDay) {
                $serviceIdsToKeep[$serviceIdOnDay][] = $dateToKeep->format('Ymd');
            }
        }
        return $serviceIdsToKeep;
    }

    /**
     * @return array<String, String[]> Get an array grouping service ids by the date (Ymd format) they are run on.
     */
    private function getCalendarDates(): array
    {
        $cachedData = $this->getCacheWithDefaultCacheUpdate(self::GTFS_ALL_CALENDAR_DATES, function (): array {
            return $this->readCalendarDates();
        }, ttl: 3 * 3600 + 10); // Cache for 3 hours 10 minutes. The additional minutes reduces the risk that multiple caches expire at the same request
        return $cachedData->getValue();
    }

    /**
     * Read the calendar_dates file from a GTFS feed. Note that this function is naive, and only supports gtfs feeds
     * where the calendar.txt file is not used, i.e. where all dates in calendar_dates are additions to an empty calendar.
     *
     * @return array<String, String[]> Get an array grouping service ids by the date (Ymd format) they are run on.
     */
    private function readCalendarDates(): array
    {
        $fileStream = fopen(self::CALENDAR_DATES_URL, 'r');
        $serviceIdsByDate = [];

        $headers = fgetcsv($fileStream);
        $SERVICE_ID_COLUMN = array_search('service_id', $headers);
        $DATE_COLUMN = array_search('date', $headers);

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
     * Get an array mapping each trip_id to the stops on that trip.
     * @return array<String, StopTime[]>
     */
    private function readTripStops(): array
    {
        Log::info('Reading stop_times');
        $fileStream = fopen(self::GTFS_STOP_TIMES_URL, 'r');
        $stopsByTripId = [];

        $headers = fgetcsv($fileStream); // ignore the headers
        $TRIP_ID_COLUMN = array_search('trip_id', $headers);
        $STOP_ID_COLUMN = array_search('stop_id', $headers);
        $STOP_PICKUP_TYPE = array_search('stop_pickup_type', $headers);
        $STOP_DROPOFF_TYPE = array_search('stop_dropoff_type', $headers);
        $DEPARTURE_TIME_COLUMN = array_search('departure_time', $headers);

        $numberOfStopTimes = 0;
        while ($row = fgetcsv($fileStream)) {
            $trip_id = $row[$TRIP_ID_COLUMN];
            $stopId = $row[$STOP_ID_COLUMN];
            $departure_time = $row[$DEPARTURE_TIME_COLUMN];

            if ($row[$STOP_PICKUP_TYPE] == 1 && $row[$STOP_DROPOFF_TYPE] == 1) {
                // Ignore "stops" where the train only passes, without possibility to embark or disembark.
                continue;
            }

            if (!key_exists($trip_id, $stopsByTripId)) {
                $stopsByTripId[$trip_id] = [];
            }

            # Assume all stop_times are in chronological order, we don't have time to sort this.
            $stopsByTripId[$trip_id][] = new StopTime($stopId, Carbon::createFromFormat('H:i:s', $departure_time));
            $numberOfStopTimes++;
        }
        Log::info("Read {$numberOfStopTimes} stop_times");
        fclose($fileStream);
        return $stopsByTripId;
    }

    /**
     * @return array<String,Route> An associative array mapping route ids to route objects
     */
    private function readRoutes(): array
    {
        $fileStream = fopen(self::GTFS_ROUTES_URL, 'r');
        $vehicleTypesByRouteId = [];

        $headers = fgetcsv($fileStream);
        $ROUTE_ID_COLUMN = array_search('route_id', $headers);
        $ROUTE_SHORT_NAME = array_search('route_short_name', $headers);; // Vehicle type is stored as route short name

        while ($row = fgetcsv($fileStream)) {
            $routeId = $row[$ROUTE_ID_COLUMN];
            $routeShortName = $row[$ROUTE_SHORT_NAME];
            $vehicleTypesByRouteId[$routeId] = new Route($routeId, $routeShortName);
        }
        fclose($fileStream);
        return $vehicleTypesByRouteId;
    }
}