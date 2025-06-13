<?php

namespace Irail\Repositories\Gtfs;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\GtfsTripNotFoundException;
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
        Log::debug("Getting start date for $journeyNumber at {$activeTime->format('Y-m-d H:i:s')}");
        $startDate = $this->getCacheOrUpdate(
            "getStartDate|$journeyNumber|{$activeTime->format('Ymd-Hi')}",
            function () use ($journeyNumber, $activeTime): ?Carbon {
                // This will take the start date from the GTFS calendar file
                // i.e. a query for 11:00 on a trip running 07-12 will return the trip of the same day
                // a query for 01:00 on a trip running 22:00-02:00 will return the trip starting 22:00 that day, i.e. the next trip.
                $trip = $this->getVehicleWithOriginAndDestination($journeyNumber, $activeTime);
                $yesterdayTrip = $this->getVehicleWithOriginAndDestination($journeyNumber, $activeTime->copy()->subDay());

                if ($yesterdayTrip !== false) {
                    // if yesterday's trip ends at 01:30 past midnight, we want to return it until that time + 30 minutes margin (so it doesn't instantly disappear when it comes in a few minutes late)
                    if (!$trip || ($activeTime->timestamp < $yesterdayTrip->getDestinationArrivalTime() + 1800)) {
                        return $activeTime->copy()->subDay()->setTime(0, 0)->addSeconds($yesterdayTrip->getOriginDepartureTime());
                    };
                }

                if (!$trip) {
                    // If the trip is not found, something is wrong. Do not return incorrect results, we prefer not to return any result at all in this case!
                    Log::warning("Vehicle start date could not be determined: $journeyNumber active at {$activeTime->format('Y-m-d H:i:s')}");
                    return null;
                }
                // return the date and time for departure
                return Carbon::createFromTimestamp($trip->getOriginDepartureTime());
            },
            GtfsRepository::secondsUntilGtfsCacheExpires() + rand(60, 120) // Cache until GTFS is updated. Spread random to prevent load spike. Always more than underlying cache getVehicleWithOriginAndDestination.
        );
        return $startDate->getValue();
    }

    /**
     * @param string $journeyNumber The vehicle name/id, such as IC538
     * @param DateTime $date The journey start date
     * @return false|JourneyWithOriginAndDestination
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    public function getVehicleWithOriginAndDestination(string $journeyNumber, DateTime $date): JourneyWithOriginAndDestination|false
    {
        Log::debug("getVehicleWithOriginAndDestination $journeyNumber {$date->format('Y-m-d H:i:s')}");
        $originAndDestination = $this->getCacheOrUpdate(
            "getVehicleWithOriginAndDestination|$journeyNumber|{$date->format('Ymd')}",
            function () use ($journeyNumber, $date): JourneyWithOriginAndDestination|false {
                $vehicleNumber = Tools::safeIntVal(VehicleIdTools::extractTrainNumber($journeyNumber));
                $vehicleDetailsForDate = self::getTripsWithStartAndEndForDate($date);
                if (!key_exists($vehicleNumber, $vehicleDetailsForDate)) {
                    return false;
                }
                return $this->getTripsWithStartAndEndForDate($date)[$vehicleNumber];
            }, GtfsRepository::secondsUntilGtfsCacheExpires() + rand(15, 59) // Cache until GTFS is updated
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
    public function getAlternativeVehicleWithOriginAndDestination(
        Carbon $journeyStartDate,
        JourneyWithOriginAndDestination $originalJourney
    ): array
    {
        $stops = $this->getVehicleStops($journeyStartDate, $originalJourney);
        if ($stops === false) {
            return [];
        }
        // Only search between stops where the train actually stops, since stops will also include waypoints.
        // Array_values to fix gaps between indexes after filtering
        $stops = array_values(array_filter($stops, fn (StopTime $stop) => $stop->hasPassengerExchange()));
        $results = [];
        for ($i = 1; $i < count($stops); $i++) {
            $results[] = new JourneyWithOriginAndDestination(
                $originalJourney->getJourneyType(),
                $originalJourney->getJourneyNumber(),
                $stops[$i - 1]->getStopId(),
                $stops[$i - 1]->getDepartureTimeOffset(),
                $stops[$i]->getStopId(),
                $stops[$i]->getArrivalTimeOffset()
            );
        }
        Log::debug('getAlternativeVehicleWithOriginAndDestination found '
            . count($results) . " segments for journey {$originalJourney->getJourneyNumber()}");
        return $results;
    }

    /**
     * @param DateTime $date
     * @return array<int, JourneyWithOriginAndDestination> journeys with origin and destination by their journey number. One journey number may have multiple journeys.
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    public function getTripsWithStartAndEndForDate(DateTime $date): array
    {
        $vehicleDetailsForDate = $this->getCacheOrUpdate(
            self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY . '|' . $date->format('Ymd'),
            function () use ($date): ?array {
                return $this->readGtfsJourneyIndex($date);
            },
            ttl: GtfsRepository::secondsUntilGtfsCacheExpires() + rand(1,10) // Cache until GTFS is updated
        )->getValue();

        if ($vehicleDetailsForDate === null || empty($vehicleDetailsForDate)) {
            throw new RequestOutsideTimetableRangeException(
                'Request outside of allowed date period '
                . '(' . GtfsRepository::getGtfsDaysBackwards() . ' days back, ' . GtfsRepository::getGtfsDaysForwards() . ' days forward): ' . $date->format('Y-m-d'),
                404
            );
        }
        return $vehicleDetailsForDate;
    }
    /**
     * @param DateTime $date
     * @return array<int, JourneyWithOriginAndDestination> journeys with origin and destination by their journey number. One journey number may have multiple journeys.
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    public function readGtfsJourneyIndex(DateTime $date): array
    {
        $dateStr = $date->format('Y-m-d');
        $data = file_get_contents('https://gtfs.irail.be/nmbs/gtfs/journeys/' . $dateStr . '/index.json');
        $json = json_decode($data, true);
        $results = [];
        foreach ($json as $journeyNumber => $item){
            $results[$journeyNumber] = new JourneyWithOriginAndDestination($item['type'], $journeyNumber, $item['origin'], $item['departureTime'],
                $item['destination'], $item['arrivalTime'], $item['compositionChangeLocations']);
        }
        return $results;
    }

    /**
     * @param Carbon                          $journeyStartDate
     * @param JourneyWithOriginAndDestination $originalJourney
     * @return StopTime[]|false
     */
    public function getVehicleStops(Carbon $journeyStartDate, JourneyWithOriginAndDestination $originalJourney): array|false
    {
        $dateStr = $journeyStartDate->format('Y-m-d');
        Log::debug("getAlternativeVehicleWithOriginAndDestination called for journey {$originalJourney->getJourneyNumber()} at date $dateStr");
        try {
            $vehicleData = file_get_contents('https://gtfs.irail.be/nmbs/gtfs/journeys/' . $dateStr . '/' . $originalJourney->getJourneyNumber() . '.json');
            $stops = json_decode($vehicleData, true)['stops'];
            $stops = array_map(fn($item) => new StopTime($item['id'], $item['arrivalTime'], $item['departureTime'], $item['boarding'], $item['alighting']),
                $stops);
        } catch (GtfsTripNotFoundException $e) {
            Log::error($e->getMessage());
            return false;
        }
        return $stops;
    }
}
