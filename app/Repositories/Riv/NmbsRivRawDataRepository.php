<?php

namespace Irail\Repositories\Riv;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Internal\GtfsVehicleNotFoundException;
use Irail\Exceptions\NoResultsException;
use Irail\Exceptions\Upstream\UpstreamParameterException;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\CachedData;
use Irail\Models\Vehicle;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\Traits\BasedOnHafas;
use Irail\Traits\Cache;
use Irail\Util\VehicleIdTools;

class NmbsRivRawDataRepository
{
    use Cache;
    use BasedOnHafas;

    const string JOURNEY_DETAIL_REF_PREFIX = 'journeyDetailRef|';
    const int LIVEBOARD_TTL = 30;
    const int JOURNEYPLANNER_TTL = 30;
    const int VEHICLE_JOURNEY_DATA = 30;
    const int VEHICLE_JOURNEY_REF_TTL = 150;
    private StationsRepository $stationsRepository;
    private RivClient $rivClient;


    /**
     * @param StationsRepository $stationsRepository
     */
    public function __construct(StationsRepository $stationsRepository, CurlProxy $curlProxy)
    {
        $this->stationsRepository = $stationsRepository;
        $this->setCachePrefix('NMBS');
        $this->rivClient = new RivClient($curlProxy);
    }

    /**
     * @param LiveboardRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getLiveboardData(LiveboardRequest $request): CachedData
    {
        // This ensures the station exists, before we send a request
        $station = $this->stationsRepository->getStationById($request->getStationId()); 
        $hafasStationId = $this->iRailToHafasId($station->getId());

        $url = 'https://mobile-riv.api.belgianrail.be/api/v1.0/dacs';
        $formattedDateTimeStr = $request->getDateTime()->format('Y-m-d H:i:s');

        $queryType = ($request->getDepartureArrivalMode() == TimeSelection::ARRIVAL)
            ? 'ArrivalsApp'
            : 'DeparturesApp';

        $parameters = [
            'query'    => $queryType, // include intermediate stops along the way
            'UicCode'  => $hafasStationId,
            'FromDate' => $formattedDateTimeStr, // requires date in 'yyyy-mm-dd hh:mm:ss' format
            'Count'    => 100, // 100 results
            // language is not passed, responses contain both Dutch and French destinations
        ];
        return $this->rivClient->makeApiCallToMobileRivApi($url, $parameters, self::LIVEBOARD_TTL);
    }

    /**
     * @param JourneyPlanningRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getRoutePlanningData(JourneyPlanningRequest $request): CachedData
    {
        // This ensures the station exists, before we send a request
        $origin = $this->stationsRepository->getStationById($request->getOriginStationId());
        $destination = $this->stationsRepository->getStationById($request->getDestinationStationId());
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey';

        $typeOfTransportCode = NmbsRivApiTransportTypeFilter::forTypeOfTransportFilter(
            $origin->getId(),
            $destination->getId(),
            $request->getTypesOfTransport()
        );

        $formattedDateStr = $request->getDateTime()->format('Y-m-d');
        $formattedTimeStr = $request->getDateTime()->format('H:i:s');

        $parameters = [
            'originExtId' => self::iRailToHafasId($origin->getId()),
            'destExtId'   => self::iRailToHafasId($destination->getId()),
            'date'             => $formattedDateStr, // requires date in yyyy-mm-dd format
            'time'             => $formattedTimeStr, // requires time in hh:mm:ss format
            'lang'             => $request->getLanguage(),
            'passlist'         => true, // include intermediate stops along the way
            'searchForArrival' => ($request->getTimeSelection() == TimeSelection::ARRIVAL), // include intermediate stops along the way
            'numF'             => 6, // request 6 (the max) results forward in time
            'products'         => $typeOfTransportCode->value
        ];
        return $this->rivClient->makeApiCallToMobileRivApi($url, $parameters, self::JOURNEYPLANNER_TTL);
    }

    /**
     * Get data for a DatedVehicleJourney (also known as vehicle or trip, one vehicle making an A->B run on a given date)
     *
     * @param VehicleJourneyRequest $request
     * @return CachedData
     * @throws GtfsVehicleNotFoundException
     */
    public function getVehicleJourneyData(VehicleJourneyRequest $request): CachedData
    {
        $announcedJourneyNumber = VehicleIdTools::extractTrainNumber($request->getVehicleId());
        $cachedJourneyDetailRef = $this->getCachedJourneyDetailRef($announcedJourneyNumber, $request);

        $journeyDetailRef = $cachedJourneyDetailRef->getValue();
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey/detail';
        $parameters = [
            'id'   => $journeyDetailRef,
            'lang' => $request->getLanguage()
        ];
        try {
            $journeyDetailResponse = $this->rivClient->makeApiCallToMobileRivApi($url, $parameters, self::VEHICLE_JOURNEY_DATA);
        } catch (UpstreamParameterException $e) {
            Log::warning('Journey detail refs are likely outdated, clearing journey detail ref cache! Exception while trying to get data:'
                . $e->getMessage());
            // SVC_PARAM exception is returned when the parameter is invalid.
            // In this case, the Hafas data has likely been updated, and all journey references need to be refreshed
            $this->deleteCachedObjectsByPrefix(self::JOURNEY_DETAIL_REF_PREFIX);
            // Retry after clearing the cache
            return $this->getVehicleJourneyData($request);
        }
        // cachedJourneyDetailRef createdAt/maxAge should not be included in this response, as it is purely internal and cached a long time for performance
        // The real data is updated independently
        return $journeyDetailResponse;
    }

    /**
     * @param VehicleJourneyRequest $request
     * @throw
     * @return string
     */
    private function getJourneyDetailRef(VehicleJourneyRequest $request): string
    {
        /** @var GtfsTripStartEndExtractor $gtfsTripExtractor */
        $gtfsTripExtractor = App::make(GtfsTripStartEndExtractor::class);
        $vehicleWithOriginAndDestination = $gtfsTripExtractor->getVehicleWithOriginAndDestination($request->getVehicleId(), $request->getDateTime());
        if ($vehicleWithOriginAndDestination === false) {
            throw new GtfsVehicleNotFoundException($request->getVehicleId());
        }

        $journeyDetailRef = self::findVehicleJourneyRefBetweenStops($request, $vehicleWithOriginAndDestination);
        # If false, the journey might have been partially cancelled. Try to find it by searching for parts of the journey
        if ($journeyDetailRef === false) {
            $journeyDetailRef = $this->getJourneyDetailRefForPartiallyCanceledTrip($gtfsTripExtractor, $request, $vehicleWithOriginAndDestination);
        }

        Log::debug("Found journey detail ref: '{$journeyDetailRef}' between {$vehicleWithOriginAndDestination->getOriginStopId()} and destination {$vehicleWithOriginAndDestination->getdestinationStopId()}");
        # If no reference has been found at this stage, fail
        if ($journeyDetailRef === false) {
            throw new NoResultsException('No vehicle journey reference could be found for vehicle ' . $request->getVehicleId());
        }
        return $journeyDetailRef;
    }

    /**
     * @param VehicleJourneyRequest           $request
     * @param JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return string|false The vehicle journey reference, or false if no reference could be found.
     */
    private function findVehicleJourneyRefBetweenStops(
        VehicleJourneyRequest $request,
        JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
    ): string|false {
        $splitOrJoinStopIds = $vehicleWithOriginAndDestination->getSplitOrJoinStopIds();
        if (empty($splitOrJoinStopIds)) {
            $startStopCombinations = [
                [$vehicleWithOriginAndDestination->getOriginStopId(), $vehicleWithOriginAndDestination->getDestinationStopId()]
            ];
        } else {
            // For some reason, some trains need to be sought specifically using their first or last segment. This code ensures those two extra segments are tried.
            $startStopCombinations = [
                [$vehicleWithOriginAndDestination->getOriginStopId(), $vehicleWithOriginAndDestination->getDestinationStopId()],
                [$vehicleWithOriginAndDestination->getOriginStopId(), $splitOrJoinStopIds[0]],
                [end($splitOrJoinStopIds), $vehicleWithOriginAndDestination->getDestinationStopId()],
            ];
            Log::info("Multiple journeys will be tried in order to obtain reference for journey {$request->getVehicleId()} consisting of " . (count($splitOrJoinStopIds) + 1) . ' segments');
        }
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey';
        $formattedDateStr = $request->getDateTime()->format('Y-m-d');
        $vehicleName = $vehicleWithOriginAndDestination->getJourneyType() . $vehicleWithOriginAndDestination->getJourneyNumber();
        $journeyRef = false;
        $combinationIndex = 0;
        while ($journeyRef === false && $combinationIndex < count($startStopCombinations)) {
            $parameters = [
                'trainFilter' => $vehicleName, // type + number, type is required!
                'originExtId' => $startStopCombinations[$combinationIndex][0],
                'destExtId'   => $startStopCombinations[$combinationIndex][1],
                'date'        => $formattedDateStr,
                'lang'        => $request->getLanguage()
            ];
            // This is already cached on a higher level, so no need to cache individual requests
            $journeyResponse = $this->rivClient->makeApiCallToMobileRivApi($url, $parameters, -1)->getValue();
            if ($journeyResponse === null || !key_exists('Trip', $journeyResponse)) {
                // No journeyref found, move on to the next combination
            } else {
                // Ref found
                $journeyRef = $journeyResponse['Trip'][0]['LegList']['Leg'][0]['JourneyDetailRef']['ref'];
            }
            $combinationIndex++;
        }
        return $journeyRef;
    }

    /**
     * Get the journey detail reference by trying alternative origin-destination stretches, to cope with cancelled origin/destination stops
     * @param GtfsTripStartEndExtractor       $gtfsTripExtractor
     * @param VehicleJourneyRequest           $request
     * @param JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return string|bool
     */
    private function getJourneyDetailRefForPartiallyCanceledTrip(
        GtfsTripStartEndExtractor $gtfsTripExtractor,
        VehicleJourneyRequest $request,
        JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
    ): string|bool {
        $alternativeOriginDestinations = $gtfsTripExtractor->getAlternativeVehicleWithOriginAndDestination(
            $vehicleWithOriginAndDestination
        );
        $i = 0;
        $journeyRef = false;
        while ($journeyRef === false && $i < count($alternativeOriginDestinations) / 2) {
            Log::debug("Searching for vehicle {$request->getVehicleId()} using alternative segments, $i");
            $altVehicleWithOriginAndDestination = $alternativeOriginDestinations[$i];
            $journeyRef = $this->findVehicleJourneyRefBetweenStops($request, $altVehicleWithOriginAndDestination);
            if ($journeyRef === false) {
                // Alternate searching from the front and the back, since cancelled first/last stops are the most common.
                $j = (count($alternativeOriginDestinations) - 1) - $i;
                Log::debug("Searching for vehicle {$request->getVehicleId()} using alternative segments, $j");
                $altVehicleWithOriginAndDestination = $alternativeOriginDestinations[$j];
                $journeyRef = $this->findVehicleJourneyRefBetweenStops($request, $altVehicleWithOriginAndDestination);
            }
            $i++;
        }
        if ($journeyRef === false) {
            Log::warning("Failed to find journey ref for {$vehicleWithOriginAndDestination->getJourneyNumber()}, trip {$vehicleWithOriginAndDestination->getTripId()}");
        }
        return $journeyRef;
    }

    /**
     * @param string                $announcedJourneyNumber
     * @param VehicleJourneyRequest $request
     * @return mixed
     */
    public function getCachedJourneyDetailRef(string $announcedJourneyNumber, VehicleJourneyRequest $request): CachedData
    {
        $journeyRefCacheKey = self::JOURNEY_DETAIL_REF_PREFIX . "{$announcedJourneyNumber}|{$request->getDateTime()->format('Ymd')}";
        $cachedJourneyDetailRef = $this->getCacheOrUpdate(
            $journeyRefCacheKey,
            function () use ($request) {
                try {
                    return $this->getJourneyDetailRef($request);
                } catch (GtfsVehicleNotFoundException $e) {
                    return false;
                }
            },
            6 * 3600 // Journey detail references may change, but in that case the cache should be cleared.
        );
        if ($cachedJourneyDetailRef->getValue() === false) {
            // If an exception was cached, throw it
            throw new GtfsVehicleNotFoundException($request->getVehicleId());
        }
        return $cachedJourneyDetailRef;
    }


    /**
     * Get the composition for a vehicle.
     *
     * @param Vehicle                         $vehicle
     * @param JourneyWithOriginAndDestination $journeyWithOriginAndDestination
     * @return CachedData
     */
    public function getVehicleCompositionData(Vehicle $vehicle, JourneyWithOriginAndDestination $journeyWithOriginAndDestination): CachedData
    {
        $url = 'https://mobile-riv.api.belgianrail.be/api/v1/commercialtraincompositionsbetweenptcars';
        $parameters = [
            'TrainNumber'         => $vehicle->getNumber(),
            'From'                => $journeyWithOriginAndDestination->getOriginStopId(),
            'To'                  => $journeyWithOriginAndDestination->getDestinationStopId(),
            'date'                => $vehicle->getJourneyStartDate()->format('Y-m-d'),
            'FromToAreUicCodes'   => 'true',
            'IncludeMaterialInfo' => 'true'
        ];
        return $this->rivClient->makeApiCallToMobileRivApi($url, $parameters, 300);
    }
}
