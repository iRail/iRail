<?php

namespace Irail\Repositories\Gtfs;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Request\RequestOutsideTimetableRangeException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Gtfs\Models\StopTime;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Repositories\Nmbs\Tools\VehicleIdTools;
use Irail\Traits\Cache;

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

    public function getStartDate(int $journeyNumber, Carbon $activeTime): Carbon
    {
        $startDate = $this->getCacheOrUpdate("getStartDate|$journeyNumber|{$activeTime->format('Ymd-Hi')}",
            function () use ($journeyNumber, $activeTime): Carbon {
                // This will take the start date from the GTFS calendar file
                // i.e. a query for 11:00 on a trip running 07-12 will return the trip of the same day
                // a query for 01:00 on a trip running 22:00-02:00 will return the trip starting 22:00 that day, i.e. the next trip.
                $originAndDestination = $this->getVehicleWithOriginAndDestination($journeyNumber, $activeTime);

                if (!$originAndDestination) {
                    // The trip could not be found for the given start date, so it must be in the past!
                    $originAndDestination = $this->getVehicleWithOriginAndDestination($journeyNumber, $activeTime->copy()->subDay());
                    if (!$originAndDestination) {
                        // If still not found, something is wrong. Do not return incorrect results, we prefer not to return any result at all in this case!
                        throw new InternalProcessingException(500,
                            "Vehicle start date could not be determined: $journeyNumber active at {$activeTime->format('Y-m-d H:i:s')}");
                    }
                    return $activeTime->copy()->subDay()->setTime(0, 0);
                }

                // A trip cannot start past 23:59:59, since it simply would have a different start date in that case
                if ($activeTime->secondsSinceMidnight() < $originAndDestination->getOriginDepartureTimeOffset()) {
                    return $activeTime->copy()->subDay()->setTime(0, 0);
                }
                return $activeTime->copy()->setTime(0, 0);
            }, ttl: 4 * 3600);// Cache for 4 hours
        return $startDate->getValue();
    }

    /**
     * @param string   $vehicleId The vehicle name/id, such as IC538
     * @param DateTime $date The date
     * @return false|JourneyWithOriginAndDestination
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    public function getVehicleWithOriginAndDestination(string $vehicleId, DateTime $date): JourneyWithOriginAndDestination|false
    {
        $vehicleNumber = Tools::safeIntVal(VehicleIdTools::extractTrainNumber($vehicleId));
        $vehicleDetailsForDate = self::getTripsWithStartAndEndByDate($date);
        $foundVehicleWithInternationalOriginAndDestination = false;

        $matches = array_filter($vehicleDetailsForDate, fn($journey) => $journey->getJourneyNumber() == $vehicleNumber);
        foreach ($matches as $vehicleWithOriginAndDestination) {
            // International journeys are split into two parts at tha Belgian side of the border, where the
            // border is represented by a "station" with a belgian ID.
            // If the journey is between belgian stops, return immediatly
            if ($this->isBelgianJourney($vehicleWithOriginAndDestination)) {
                return $vehicleWithOriginAndDestination;
            }
            // Otherwise, keep the "international" stretch as a last-change backup should we not find a belgian part.
            $foundVehicleWithInternationalOriginAndDestination = $vehicleWithOriginAndDestination;
        }
        if ($foundVehicleWithInternationalOriginAndDestination) {
            return $foundVehicleWithInternationalOriginAndDestination;
        }
        return false;
    }


    /**
     * Get all successive stops for a vehicle, for use in RIV vehicle search where two non-cancelled points are needed to find a vehicle.
     * This method is only needed when one of the first/last stops is cancelled.
     *
     * @param JourneyWithOriginAndDestination $originalJourney
     * @return JourneyWithOriginAndDestination[]
     * @throws Exception
     */
    public function getAlternativeVehicleWithOriginAndDestination(JourneyWithOriginAndDestination $originalJourney): array
    {
        Log::debug("getAlternativeVehicleWithOriginAndDestination called for trip {$originalJourney->getTripId()}");
        $stops = self::getStopsForTrip($originalJourney->getTripId());
        $results = [];
        for ($i = 1; $i < count($stops); $i++) {
            $results[] = new JourneyWithOriginAndDestination(
                $originalJourney->getTripId(),
                $originalJourney->getJourneyType(),
                $originalJourney->getJourneyNumber(),
                $stops[$i - 1]->getStopId(),
                $stops[$i - 1]->getDepartureTimeOffset(),
                $stops[$i]->getStopId(),
                $stops[$i]->getDepartureTimeOffset()
            );
        }
        Log::debug('getAlternativeVehicleWithOriginAndDestination found '
            . count($results) . " for trip {$originalJourney->getTripId()}");
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
     * @return JourneyWithOriginAndDestination[]
     * @throws RequestOutsideTimetableRangeException | UpstreamServerException
     */
    private function getTripsWithStartAndEndByDate(DateTime $date): array
    {
        $vehicleDetailsByDate = $this->getTripsWithStartAndEndDate();
        $dateYmd = $date->format('Ymd');
        if (!key_exists($dateYmd, $vehicleDetailsByDate)) {
            throw new RequestOutsideTimetableRangeException('Request outside of allowed date period (3 days back, 14 days forward)', 404);
        }
        return $vehicleDetailsByDate[$dateYmd];
    }

    /**
     * @return array<string, JourneyWithOriginAndDestination[]> An array containing VehicleWithOriginAndDestination objects grouped by date in Ymd format
     */
    private function getTripsWithStartAndEndDate(): array
    {
        $vehicleDetailsByDate = $this->getCacheOrUpdate(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY, function (): array {
            return $this->loadTripsWithStartAndEndDate();
        }, ttl: 4 * 3600);
        return $vehicleDetailsByDate->getValue(); // Cache for 4 hours
    }

    /**
     * @return array<string, JourneyWithOriginAndDestination[]> An array containing VehicleWithOriginAndDestination objects grouped by date in Ymd format
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
        // date (Ymd) => VehicleWithOriginAndDestination[]
        $vehicleDetailsByDate = [];
        foreach ($tripsByJourneyAndDate as $journeyNumber => $journeysByDate) {
            foreach ($journeysByDate as $date => $trip) {
                if (!key_exists($date, $vehicleDetailsByDate)) {
                    $vehicleDetailsByDate[$date] = [];
                }
                $stops = $tripStops[$trip->getTripId()];
                $firstStop = $stops[0];
                $lastStop = end($stops);
                $vehicleDetailsByDate[$date][] = new JourneyWithOriginAndDestination( // DO NOT index on journeyNumber, since some journeys may be split and therefore occur twice.
                    $trip->getTripId(),
                    $trip->getJourneyType(),
                    $trip->getJourneyNumber(),
                    $firstStop->getStopId(),
                    $firstStop->getDepartureTimeOffset(),
                    $lastStop->getStopId(),
                    $lastStop->getDepartureTimeOffset()
                );
            }
        }
        return $vehicleDetailsByDate;
    }

    /**
     * @return StopTime[]
     * @throws Exception
     */
    private function getStopsForTrip(string $tripId): array
    {
        $tripStops = $this->gtfsRepository->getTripStops();

        if (!key_exists($tripId, $tripStops)) {
            throw new Exception('Trip not found', 404);
        }

        return $tripStops[$tripId];
    }
}
