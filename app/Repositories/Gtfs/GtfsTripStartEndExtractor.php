<?php

namespace Irail\Repositories\Gtfs;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Irail\Repositories\Gtfs\Models\VehicleWithOriginAndDestination;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Repositories\Nmbs\Tools\VehicleIdTools;
use Irail\Traits\Cache;
use function Irail\Data\NMBS\Repositories\Gtfs\str_starts_with;

class GtfsTripStartEndExtractor
{
    use Cache;

    const GTFS_TRIP_STOPS_CACHE_KEY = 'stopsByTrip';
    const GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY = 'vehicleDetailsByDate';
    private GtfsRepository $gtfsRepository;

    public function __construct(GtfsRepository $gtfsRepository = null)
    {
        $this->gtfsRepository = $gtfsRepository;
        if ($gtfsRepository == null) {
            $this->gtfsRepository = new GtfsRepository();
        }
        $this->setCachePrefix('gtfsTrips');
    }

    /**
     * @param string   $vehicleId The vehicle name/id, such as IC538
     * @param DateTime $date The date
     * @return false|VehicleWithOriginAndDestination
     * @throws Exception
     */
    public function getVehicleWithOriginAndDestination(string $vehicleId, DateTime $date): VehicleWithOriginAndDestination|false
    {
        $vehicleNumber = Tools::safeIntVal(VehicleIdTools::extractTrainNumber($vehicleId));
        $vehicleDetailsForDate = self::getTripsWithStartAndEndByDate($date);
        $foundVehicleWithInternationalOriginAndDestination = false;

        foreach ($vehicleDetailsForDate as $vehicleWithOriginAndDestination) {
            if ($vehicleWithOriginAndDestination->getVehicleNumber() == $vehicleNumber) {
                // International journeys are split into two parts at tha Belgian side of the border, where the
                // border is represented by a "station" with a belgian ID.
                // If the journey is between belgian stops, return immediatly
                if ($this->isBelgianJourney($vehicleWithOriginAndDestination)) {
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
    public function getAlternativeVehicleWithOriginAndDestination(VehicleWithOriginAndDestination $originalVehicle): array
    {
        Log::debug("getAlternativeVehicleWithOriginAndDestination called for trip {$originalVehicle->getTripId()}");
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
        Log::debug('getAlternativeVehicleWithOriginAndDestination found '
            . count($results) . " for trip {$originalVehicle->getTripId()}");
        return $results;
    }

    /**
     * @param VehicleWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return bool
     */
    private function isBelgianJourney(VehicleWithOriginAndDestination $vehicleWithOriginAndDestination): bool
    {
        return str_starts_with($vehicleWithOriginAndDestination->getOriginStopId(), '88')
            && str_starts_with($vehicleWithOriginAndDestination->getDestinationStopId(), '88');
    }

    /**
     * @param DateTime $date
     * @return VehicleWithOriginAndDestination[]
     * @throws Exception
     */
    private function getTripsWithStartAndEndByDate(DateTime $date): array
    {
        $vehicleDetailsByDate = $this->getCacheWithDefaultCacheUpdate(self::GTFS_VEHICLE_DETAILS_BY_DATE_CACHE_KEY, function (): array {
            return $this->loadTripsWithStartAndEndDate();
        }, ttl: 2 * 3600); // Cache for 2 hours

        $vehicleDetailByDate = $vehicleDetailsByDate->getValue();
        $dateYmd = $date->format('Ymd');
        if (!key_exists($dateYmd, $vehicleDetailByDate)) {
            throw new Exception('Request outside of allowed date period (3 days back, 14 days forward)', 404);
        }
        return $vehicleDetailsByDate[$dateYmd];
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private function getStopsForTrip(string $tripId): array
    {
        $gtfsRepository = $this->gtfsRepository;
        $tripStops = $this->getCacheWithDefaultCacheUpdate(self::GTFS_TRIP_STOPS_CACHE_KEY, function () use ($gtfsRepository): array {
            return $gtfsRepository->readTripStops();
        }, ttl: 3 * 3600 + 1); // Cache for 3 hours 1 minute. The additional minute reduces the risk that both the stops cache and the trips cache expire
        // at the same time

        $tripStops = $tripStops->getValue();

        if (!key_exists($tripId, $tripStops)) {
            throw new Exception('Trip not found', 404);
        }
        return $tripStops[$tripId];
    }

    /**
     * @return array<string, VehicleWithOriginAndDestination[]> An array containing VehicleWithOriginAndDestination objects grouped by date in ymd format
     * @throws Exception
     */
    private function loadTripsWithStartAndEndDate(): array
    {
        $serviceIdsByCalendarDate = $this->gtfsRepository->readCalendarDates();
        // Only keep service ids in a specific date range (x days back, y days forward), to keep cpu/ram usage down
        $serviceIdsToRetain = $this->gtfsRepository->getServiceIdsInDateRange($serviceIdsByCalendarDate, 3, 14);
        $vehicleDetailsByServiceId = $this->gtfsRepository->readTripsGroupedByServiceId($serviceIdsToRetain);

        if (empty($serviceIdsByCalendarDate) || empty($vehicleDetailsByServiceId)) {
            throw new Exception('No response from iRail GTFS', 504);
        }

        // Create a multidimensional array:
        // date (yyyymmdd) => VehicleWithOriginAndDestination[]
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
        return $vehicleDetailsByDate;
    }

}
