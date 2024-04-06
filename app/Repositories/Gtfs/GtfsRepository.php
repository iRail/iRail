<?php

namespace Irail\Repositories\Gtfs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Irail\Models\CachedData;
use Irail\Repositories\Gtfs\Models\PickupDropoffType;
use Irail\Repositories\Gtfs\Models\Route;
use Irail\Repositories\Gtfs\Models\StopTime;
use Irail\Repositories\Gtfs\Models\Trip;
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
    const string GTFS_ROUTES_CACHE_KEY = 'routes';

    /**
     * @return int The number of days in the past to read from GTFS files. Affects all endpoints using this data!
     */
    public static function getGtfsDaysBackwards(): int
    {
        return intval(env('GTFS_RANGE_DAYS_BACKWARDS', 3));
    }

    /**
     * @return int The number of days in the future to read from GTFS files. Affects all endpoints using this data!
     */
    public static function getGtfsDaysForwards(): int
    {
        return intval(env('GTFS_RANGE_DAYS_FORWARDS', 14));
    }

    public function __construct()
    {
        $this->setCachePrefix('GtfsRepository');
    }

    /**
     * @return Array<String, Array<String, Trip>> Trips by their journey number and start date (Ymd format)
     */
    public function getTripsByJourneyNumberAndStartDate(): array
    {
        $cachedData = $this->getCacheOrSynchronizedUpdate(self::GTFS_ALL_TRIPS, function (): array {
            return $this->readTripsByJourneyNumberAndStartDate();
        }, ttl: 3 * 3600 + 4); // The additional minutes reduces the risk that both the stops cache and the trips cache expire at the same request.
        return $cachedData->getValue();
    }

    public function getCachedTrips(): false|CachedData
    {
        if (!$this->isCached(self::GTFS_ALL_TRIPS)) {
            return false;
        }
        return $this->getCachedObject(self::GTFS_ALL_TRIPS);
    }

    /**
     * Get an array mapping each trip_id to the stops on that trip.
     * @return array<String, StopTime[]>
     */
    public function getTripStops(): array
    {
        $cachedData = $this->getCacheOrSynchronizedUpdate(self::GTFS_ALL_TRIP_STOPS_CACHE_KEY, function (): array {
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
        $cachedData = $this->getCacheOrUpdate(self::GTFS_ROUTES_CACHE_KEY, function (): array {
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
        Log::info('readTripsByJourneyNumberAndStartDate');
        $trips = [];

        $serviceIdsToRetain = $this->getServiceIdsInDateRange(self::getGtfsDaysBackwards(), self::getGtfsDaysForwards());
        $vehicleTypeByRouteId = $this->getRouteIdToJourneyTypeMap();

        Log::info('Reading ' . self::TRIPS_URL . ', retaining trips for ' . count($serviceIdsToRetain) . ' service ids');
        $fileStream = fopen(self::TRIPS_URL, 'r');

        $headers = fgetcsv($fileStream);
        $SERVICE_ID_COLUMN = array_search('service_id', $headers);
        $ROUTE_ID_COLUMN = array_search('route_id', $headers);
        $TRIP_ID_COLUMN = array_search('trip_id', $headers);
        $TRIP_SHORT_NAME_COLUMN = array_search('trip_short_name', $headers);

        $numberOfSkippedTrips = 0;
        $numberOfImportedTrips = 0;
        while ($row = fgetcsv($fileStream)) {
            $serviceId = $row[$SERVICE_ID_COLUMN];
            if (!key_exists($serviceId, $serviceIdsToRetain)) {
                $numberOfSkippedTrips++;
                continue;
            }
            $activeDatesYmd = $serviceIdsToRetain[$serviceId];

            $tripId = $row[$TRIP_ID_COLUMN];
            $journeyNumber = $row[$TRIP_SHORT_NAME_COLUMN];

            if (!key_exists($journeyNumber, $trips)) {
                $trips[$journeyNumber] = []; // Initialize new array
            }

            $routeId = $row[$ROUTE_ID_COLUMN];
            $journeyType = $vehicleTypeByRouteId[$routeId];

            $trip = new Trip($tripId, $journeyType, $journeyNumber);
            foreach ($activeDatesYmd as $activeDateYmd) {
                // A journey number might have different services (trips) on different dates, especially when the GTFS data covers multiple timetable periods
                $trips[$journeyNumber][$activeDateYmd] = $trip;
            }
            $numberOfImportedTrips++; // Count so we can verify if and how many trips are lost due to having the same journey number
        }
        fclose($fileStream);
        Log::info('readTripsByJourneyNumberAndStartDate found ' . count($trips) . " journey ids after importing $numberOfImportedTrips trips. Skipped $numberOfSkippedTrips trips. " . $this->getMemoryUsage());
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
        Log::info("Searching for service days in range -{$daysBack}, {$daysForward}");
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
        Log::info('Found ' . count($serviceIdsToKeep) . ' service ids in date range. ' . $this->getMemoryUsage());
        return $serviceIdsToKeep;
    }

    /**
     * @return array<String, String[]> Get an array grouping service ids by the date (Ymd format) they are run on.
     */
    private function getCalendarDates(): array
    {
        $cachedData = $this->getCacheOrUpdate(self::GTFS_ALL_CALENDAR_DATES, function (): array {
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
        Log::info('Reading ' . self::CALENDAR_DATES_URL);
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
            $serviceId = $row[$SERVICE_ID_COLUMN];
            $serviceIdsByDate[$date][] = $serviceId;
        }
        Log::info('Read ' . count($serviceIdsByDate) . ' dates. ' . $this->getMemoryUsage());
        return $serviceIdsByDate;
    }

    /**
     * Get an array mapping each trip_id to the stops on that trip.
     * @return array<String, StopTime[]>
     */
    private function readTripStops(): array
    {
        Log::info('Reading ' . self::GTFS_STOP_TIMES_URL);

        $tripIdsToRetain = [];
        foreach ($this->getTripsByJourneyNumberAndStartDate() as $tripsByStartDate) {
            foreach ($tripsByStartDate as $trip) {
                $tripIdsToRetain[$trip->getTripId()] = 1;
            }
        }

        $fileStream = fopen(self::GTFS_STOP_TIMES_URL, 'r');
        $stopsByTripId = [];

        $headers = fgetcsv($fileStream); // ignore the headers
        $TRIP_ID_COLUMN = array_search('trip_id', $headers);
        $STOP_ID_COLUMN = array_search('stop_id', $headers);
        $STOP_PICKUP_TYPE = array_search('pickup_type', $headers);
        $STOP_DROPOFF_TYPE = array_search('drop_off_type', $headers);
        $DEPARTURE_TIME_COLUMN = array_search('departure_time', $headers);
        $ARRIVAL_TIME_COLUMN = array_search('arrival_time', $headers);

        $numberOfStopTimes = 0;
        $numberOfSkippedStopTimes = 0;
        while ($row = fgetcsv($fileStream)) {
            $trip_id = $row[$TRIP_ID_COLUMN];

            if (!key_exists($trip_id, $tripIdsToRetain)) {
                // only keep trips for which we have the start date as well
                $numberOfSkippedStopTimes++;
                continue;
            }

            $stopId = $row[$STOP_ID_COLUMN];
            $arrival_time = $row[$ARRIVAL_TIME_COLUMN];
            $departure_time = $row[$DEPARTURE_TIME_COLUMN];

            $pickup_type = PickupDropoffType::from(intval($row[$STOP_PICKUP_TYPE]));
            $dropoff_type = PickupDropoffType::from(intval($row[$STOP_DROPOFF_TYPE]));

            if (!key_exists($trip_id, $stopsByTripId)) {
                $stopsByTripId[$trip_id] = [];
            }

            # Assume all stop_times are in chronological order, we don't have time to sort this.
            $stopsByTripId[$trip_id][] = new StopTime($stopId, $arrival_time, $departure_time, $dropoff_type, $pickup_type);
            $numberOfStopTimes++;
        }
        fclose($fileStream);
        Log::info("Read {$numberOfStopTimes} stop_times, skipped {$numberOfSkippedStopTimes}. " . $this->getMemoryUsage());
        return $stopsByTripId;
    }

    /**
     * @return array<String,Route> An associative array mapping route ids to route objects
     */
    private function readRoutes(): array
    {
        Log::info('Reading ' . self::GTFS_ROUTES_URL);
        $fileStream = fopen(self::GTFS_ROUTES_URL, 'r');
        $vehicleTypesByRouteId = [];

        $headers = fgetcsv($fileStream);
        $ROUTE_ID_COLUMN = array_search('route_id', $headers);
        $ROUTE_SHORT_NAME = array_search('route_short_name', $headers); // Vehicle type is stored as route short name

        while ($row = fgetcsv($fileStream)) {
            $routeId = $row[$ROUTE_ID_COLUMN];
            $routeShortName = $row[$ROUTE_SHORT_NAME];
            $vehicleTypesByRouteId[$routeId] = new Route($routeId, $routeShortName);
        }
        fclose($fileStream);
        Log::info('Read ' . count($vehicleTypesByRouteId) . ' GTFS routes.txt. ' . $this->getMemoryUsage());
        return $vehicleTypesByRouteId;
    }

    private function getMemoryUsage(): string
    {
        return 'Memory usage ' . round(memory_get_usage() / 1024 / 1024, 2) . 'MB, peak ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB';
    }
}