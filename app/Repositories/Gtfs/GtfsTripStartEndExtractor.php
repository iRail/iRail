<?php

namespace Irail\Repositories\Gtfs;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\GtfsTripNotFoundException;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Request\RequestOutsideTimetableRangeException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Gtfs\Models\StopTime;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Traits\Cache;
use Irail\Util\VehicleIdTools;

class GtfsTripStartEndExtractor
{
    use Cache;

    const string GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY = 'vehicleDetailsByDate';
    private GtfsRepository $gtfsRepository;

    /**
     * @var array An array cache for vehicle details by date, to reduce deserializing when frequently accessing this data.
     * Even reading from cache is slow for this data.
     */
    private array $vehicleDetailsByDate = [];

    public function __construct(?GtfsRepository $gtfsRepository = null)
    {
        if ($gtfsRepository == null) {
            $this->gtfsRepository = new GtfsRepository();
        } else {
            $this->gtfsRepository = $gtfsRepository;
        }
        $this->setCachePrefix('gtfsTrips');
    }

    public function getStartDate(int $journeyNumber, Carbon $activeTime): ?Carbon
    {
        $startDate = $this->getCacheOrUpdate(
            "getStartDate|$journeyNumber|{$activeTime->format('Ymd-Hi')}",
            function () use ($journeyNumber, $activeTime): ?Carbon {
                // This will take the start date from the GTFS calendar file
                // i.e. a query for 11:00 on a trip running 07-12 will return the trip of the same day
                // a query for 01:00 on a trip running 22:00-02:00 will return the trip starting 22:00 that day, i.e. the next trip.
                $originAndDestination = $this->getVehicleWithOriginAndDestination($journeyNumber, $activeTime);

                if (!$originAndDestination) {
                    // The trip could not be found for the given start date, so it must be in the past!
                    $originAndDestination = $this->getVehicleWithOriginAndDestination($journeyNumber, $activeTime->copy()->subDay());
                    if (!$originAndDestination) {
                        // If still not found, something is wrong. Do not return incorrect results, we prefer not to return any result at all in this case!
                        Log::warning("Vehicle start date could not be determined: $journeyNumber active at {$activeTime->format('Y-m-d H:i:s')}");
                        return null;
                    }
                    return $activeTime->copy()->subDay()->setTime(0, 0);
                }

                // A trip cannot start past 23:59:59, since it simply would have a different start date in that case
                if ($activeTime->secondsSinceMidnight() < $originAndDestination->getOriginDepartureTimeOffset()) {
                    return $activeTime->copy()->subDay()->setTime(0, 0);
                }
                return $activeTime->copy()->setTime(0, 0);
            },
            GtfsRepository::secondsUntilNextGtfsUpdate() + 60 // Cache until GTFS is updated
        );
        return $startDate->getValue();
    }

    /**
     * @param string $journeyNumber The vehicle name/id, such as IC538
     * @param DateTime $date The date
     * @return false|JourneyWithOriginAndDestination
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    public function getVehicleWithOriginAndDestination(string $journeyNumber, DateTime $date): JourneyWithOriginAndDestination|false
    {
        $originAndDestination = $this->getCacheOrUpdate(
            "getVehicleWithOriginAndDestination|$journeyNumber|{$date->format('Ymd-Hi')}",
            function () use ($journeyNumber, $date): JourneyWithOriginAndDestination|false {
                $vehicleNumber = Tools::safeIntVal(VehicleIdTools::extractTrainNumber($journeyNumber));
                $vehicleDetailsForDate = self::getTripsWithStartAndEndByDate($date);
                if (!key_exists($vehicleNumber, $vehicleDetailsForDate)) {
                    return false;
                }

                $journeyParts = $vehicleDetailsForDate[$vehicleNumber];
                if (count($journeyParts) == 0) {
                    Log::warning("No matching GTFS trip for '{$vehicleNumber}' on {$date->format('Y-m-d')}");
                    return false;
                }
                if (count($journeyParts) == 1) {
                    Log::debug("Found one matching GTFS trip '{$journeyParts[0]->getTripId()}' for journey '{$vehicleNumber}'");
                    return $journeyParts[0];
                }

                if (count($journeyParts) > 4) { // If this ever occurs, it needs to be investigated and tested before it is implemented.
                    $tripIds = join(', ', array_map(fn ($match) => $match->getTripId(), $journeyParts));
                    Log::error("A journey number cannot occur more than twice on the same day! '{$vehicleNumber}' has GTFS trip ids: $tripIds");
                    throw new InternalProcessingException(
                        500,
                        "A journey number cannot occur more than twice on the same day! '{$vehicleNumber}' has GTFS trip ids: $tripIds"
                    );
                }

                $tripIds = join(', ', array_map(fn ($match) => $match->getTripId(), $journeyParts));
                Log::debug("Combining GTFS trips $tripIds for journey '$journeyNumber'");

                $journeyPartsByStart = [];
                $journeyPartsByDestination = [];
                $invalid = false;

                foreach ($journeyParts as $match) {
                    // Each origin/destination may only occur once
                    $invalid = $invalid
                        || array_key_exists($match->getOriginStopId(), $journeyPartsByStart)
                        || array_key_exists($match->getDestinationStopId(), $journeyPartsByDestination);
                    Log::debug("From {$match->getOriginStopId()} to {$match->getDestinationStopId()}");
                    $journeyPartsByStart[$match->getOriginStopId()] = $match;
                    $journeyPartsByDestination[$match->getDestinationStopId()] = $match;
                }

                # The origin is the only station which doesn't match a destination
                $origin = array_filter(array_keys($journeyPartsByStart), fn ($stopId) => !array_key_exists($stopId, $journeyPartsByDestination))[0];
                $orderedParts = [];
                $orderedParts[] = $journeyPartsByStart[$origin];
                for ($i = 1; $i < count($journeyParts); $i++) {
                    $part = $journeyPartsByStart[$orderedParts[$i - 1]->getDestinationStopId()];
                    $orderedParts[] = $part;
                }
                $origin = array_shift($orderedParts);
                $destination = array_pop($orderedParts);
                // If the two journeyParts are connected segments, return one large train origin/destination with the id from the first segment

                if (!$invalid) {
                    return new JourneyWithOriginAndDestination(
                        $origin->getTripId(),
                        $origin->getJourneyType(),
                        $origin->getJourneyNumber(),
                        $origin->getOriginStopId(),
                        $origin->getOriginDepartureTimeOffset(),
                        $destination->getDestinationStopId(),
                        $destination->getDestinationArrivalTimeOffset(),
                        $orderedParts
                    );
                }

                Log::error("'{$vehicleNumber}' number occurs twice on the same day at non-connected segments! GTFS trip ids: $tripIds");
                throw new InternalProcessingException(
                    500,
                    "'{$vehicleNumber}' occurs twice on the same day at non-connected segments! GTFS trip ids: $tripIds"
                );
            }, GtfsRepository::secondsUntilNextGtfsUpdate() + 60 // Cache until GTFS is updated
        );
        return $originAndDestination->getValue();
    }


    /**
     * Get all successive stops for a vehicle, for use in RIV vehicle search where two non-cancelled points are needed to find a vehicle.
     * This method is only needed when one of the first/last stops is cancelled.
     *
     * @param JourneyWithOriginAndDestination $originalJourney
     * @return JourneyWithOriginAndDestination[]
     */
    public function getAlternativeVehicleWithOriginAndDestination(JourneyWithOriginAndDestination $originalJourney): array
    {
        Log::debug("getAlternativeVehicleWithOriginAndDestination called for trip {$originalJourney->getTripId()}");
        try {
            $stops = self::getStopTimesForTrip($originalJourney->getTripId());
        } catch (GtfsTripNotFoundException $e) {
            Log::error($e->getMessage());
            return [];
        }
        // Only search between stops where the train actually stops, since stops will also include waypoints.
        // Array_values to fix gaps between indexes after filtering
        $stops = array_values(array_filter($stops, fn (StopTime $stop) => $stop->hasPassengerExchange()));
        $results = [];
        for ($i = 1; $i < count($stops); $i++) {
            $results[] = new JourneyWithOriginAndDestination(
                $originalJourney->getTripId(),
                $originalJourney->getJourneyType(),
                $originalJourney->getJourneyNumber(),
                $stops[$i - 1]->getStopId(),
                $stops[$i - 1]->getDepartureTimeOffset(),
                $stops[$i]->getStopId(),
                $stops[$i]->getArrivalTimeOffset()
            );
        }
        Log::debug('getAlternativeVehicleWithOriginAndDestination found '
            . count($results) . " segments for trip {$originalJourney->getTripId()}");
        return $results;
    }

    /**
     * @param JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return bool
     */
    private function isBelgianJourney(JourneyWithOriginAndDestination $vehicleWithOriginAndDestination): bool
    {
        return str_starts_with($vehicleWithOriginAndDestination->getOriginStopId(), '88')
            && str_starts_with($vehicleWithOriginAndDestination->getDestinationStopId(), '88');
    }

    /**
     * @param DateTime $date
     * @return array<int, JourneyWithOriginAndDestination[]> journeys with origin and destination by their journey number. One journey number may have multiple journeys.
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    public function getTripsWithStartAndEndByDate(DateTime $date): array
    {
        // Use an array cache on top of the cached data, as deserializing this data takes 80+ms from the cache can take up multiple seconds
        // when this method is called hundreds of times while reading large liveboards
        $dateYmd = $date->format('Ymd');
        if (key_exists($dateYmd, $this->vehicleDetailsByDate)) {
            // If we can skip the cache completely, do so! Over 10 method calls. this will shave 50ms of the response time.
            return $this->vehicleDetailsByDate[$dateYmd];
        }

        // By caching the individual array key/values, we do not need to deserialize the entire array from cache every time this method is called.
        // This reduces deserializing times from 80+ms to 5ms.
        $vehicleDetailsForDate = $this->getCacheOrUpdate(
            self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY . '|' . $dateYmd,
            function () use ($dateYmd): ?array {
                $tripsWithStartAndEndDate = $this->getTripsWithStartAndEndDate();
                if (!key_exists($dateYmd, $tripsWithStartAndEndDate)) {
                    return null;
                }
                return $tripsWithStartAndEndDate[$dateYmd];
            },
            ttl: 3600
        )->getValue();

        if ($vehicleDetailsForDate === null) {
            throw new RequestOutsideTimetableRangeException(
                'Request outside of allowed date period '
                . '(' . GtfsRepository::getGtfsDaysBackwards() . ' days back, ' . GtfsRepository::getGtfsDaysForwards() . ' days forward): ' . $dateYmd,
                404
            );
        }

        // Update the array cache
        $this->vehicleDetailsByDate[$dateYmd] = $vehicleDetailsForDate;
        return $vehicleDetailsForDate;
    }

    /**
     * @return array<string, JourneyWithOriginAndDestination[]> An array containing VehicleWithOriginAndDestination objects grouped by date in Ymd format
     */
    private function getTripsWithStartAndEndDate(): array
    {
        // IMPORTANT PERFORMANCE NOTE: Deserializing this data takes 80+ms
        // This is better than the many seconds it takes to calculate this data, but it should still be used wisely!
        $vehicleDetailsByDate = $this->getCacheOrUpdate(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY, function (): array {
            return $this->loadTripsWithStartAndEndDate();
        }, GtfsRepository::secondsUntilNextGtfsUpdate() + 60); // Cache until GTFS is updated
        return $vehicleDetailsByDate->getValue();
    }

    /**
     * @return array<string, array<int, JourneyWithOriginAndDestination[]>> An array containing VehicleWithOriginAndDestination objects grouped by date in
     *                                                                      Ymd format, then by journey number. Multiple vehicles may occur for a given
     *                                                                      journey number.
     * @throws UpstreamServerException
     */
    private function loadTripsWithStartAndEndDate(): array
    {
        // Only keep service ids in a specific date range (x days back, y days forward), to keep cpu/ram usage down
        $tripsByJourneyAndDate = $this->gtfsRepository->getTripsByJourneyNumberAndStartDate();
        $tripStops = $this->gtfsRepository->getTripStops();

        if (empty($tripsByJourneyAndDate) || empty($tripStops)) {
            throw new UpstreamServerException('No response from iRail GTFS', 504);
        }

        // Create a multidimensional array:
        // date (Ymd) => JourneyWithOriginAndDestination[]
        $vehicleDetailsByDate = [];
        foreach ($tripsByJourneyAndDate as $journeysByDate) {
            foreach ($journeysByDate as $date => $trips) {
                foreach ($trips as $trip) {
                    if (!key_exists($date, $vehicleDetailsByDate)) {
                        $vehicleDetailsByDate[$date] = [];
                    }
                    // Some journeys may be split and therefore their number may occur twice!
                    // Index on journey numbers, but allow multiple values
                    if (!key_exists($date, $vehicleDetailsByDate)) {
                        $vehicleDetailsByDate[$date][$trip->getJourneyNumber()] = [];
                    }

                    $stops = $tripStops[$trip->getTripId()];
                    $firstStop = $stops[0];
                    $lastStop = end($stops);

                    $firstStopId = explode(':', $trip->getTripId())[3];
                    $lastStopId = explode(':', $trip->getTripId())[4];

                    // Border crossing may have different ids for stops on each side of the border, so use station ids from the trip id
                    // 84____:L72::8400424:8849064:4:2230:20240608,22:18:00,22:18:00,8400424,1,,0,1,
                    // 84____:L72::8400424:8849064:4:2230:20240608,22:21:00,22:21:00,8400426,2,,0,1,
                    // 84____:L72::8400424:8849064:4:2230:20240608,22:28:00,22:28:00,8400219,3,,0,1,
                    // 88____:L72::8849064:8841004:4:2250:20240608,22:32:00,22:33:00,8846201,5,,0,0,
                    // 88____:L72::8849064:8841004:4:2250:20240608,22:42:00,22:43:00,8843901,6,,0,0,
                    // 88____:L72::8849064:8841004:4:2250:20240608,22:50:00,22:50:00,8841004,7,,1,0,
                    // We'll use the trip id as a fallback to prevent this


                    $vehicleDetailsByDate[$date][$trip->getJourneyNumber()][] = new JourneyWithOriginAndDestination(
                        $trip->getTripId(),
                        $trip->getJourneyType(),
                        $trip->getJourneyNumber(),
                        $firstStopId,
                        $firstStop->getDepartureTimeOffset(),
                        $lastStopId,
                        $lastStop->getDepartureTimeOffset()
                    );
                }
            }
        }
        return $vehicleDetailsByDate;
    }

    /**
     * Get the stop_times for this trip.
     * !!! IMPORTANT !!! stop_times may contain waypoints where no passenger exchange is possible. These should be handled correctly when showing results to users.
     * @return StopTime[] The stop times for this trip, including waypoints where the train is passing by.
     * @throws GtfsTripNotFoundException
     */
    private function getStopTimesForTrip(string $tripId): array
    {
        $tripStops = $this->gtfsRepository->getTripStops();

        if (!key_exists($tripId, $tripStops)) {
            throw new GtfsTripNotFoundException($tripId);
        }

        return $tripStops[$tripId];
    }
}
