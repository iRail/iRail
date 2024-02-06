<?php

namespace Irail\Repositories\Riv;

use Exception;
use Illuminate\Support\Facades\Log;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\CachedData;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\Traits\BasedOnHafas;
use Irail\Traits\Cache;

class NmbsRivRawDataRepository
{
    use Cache;
    use BasedOnHafas;

    private StationsRepository $stationsRepository;
    private CurlProxy $curlProxy;

    /**
     * @param StationsRepository $stationsRepository
     */
    public function __construct(StationsRepository $stationsRepository, CurlProxy $curlProxy)
    {
        $this->stationsRepository = $stationsRepository;
        $this->curlProxy = $curlProxy;
        $this->setCachePrefix('NMBS');
    }

    /**
     * @param LiveboardRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getLiveboardData(LiveboardRequest $request): CachedData
    {
        return $this->getCacheOrUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshLiveboardData($request);
        });
    }

    /**
     * @param LiveboardRequest $request
     * @return bool|string
     */
    protected function getFreshLiveboardData(LiveboardRequest $request): string|bool
    {
        $hafasStationId = $this->iRailToHafasId($request->getStationId());

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
        return $this->makeApiCallToMobileRivApi($url, $parameters);
    }

    /**
     * @param JourneyPlanningRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getRoutePlanningData(JourneyPlanningRequest $request): CachedData
    {
        return $this->getCacheOrUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshRouteplanningData($request);
        });
    }

    /**
     * @param JourneyPlanningRequest $request
     * @return string The JSON data returned by the HAFAS system
     */
    private function getFreshRouteplanningData(JourneyPlanningRequest $request): string
    {
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey';

        $typeOfTransportCode = NmbsRivApiTransportTypeFilter::forTypeOfTransportFilter(
            $request->getOriginStationId(),
            $request->getDestinationStationId(),
            $request->getTypesOfTransport());

        $formattedDateStr = $request->getDateTime()->format('Y-m-d');
        $formattedTimeStr = $request->getDateTime()->format('H:i:s');

        $parameters = [
            'originExtId'      => self::iRailToHafasId($request->getOriginStationId()),
            'destExtId'        => self::iRailToHafasId($request->getDestinationStationId()),
            'date'             => $formattedDateStr, // requires date in yyyy-mm-dd format
            'time'             => $formattedTimeStr, // requires time in hh:mm:ss format
            'lang'             => $request->getLanguage(),
            'passlist'         => true, // include intermediate stops along the way
            'searchForArrival' => ($request->getTimeSelection() == TimeSelection::ARRIVAL), // include intermediate stops along the way
            'numF'             => 6, // request 6 (the max) results forward in time
            'products'         => $typeOfTransportCode->value
        ];
        return $this->makeApiCallToMobileRivApi($url, $parameters);
    }

    /**
     * Get data for a DatedVehicleJourney (also known as vehicle or trip, one vehicle making an A->B run)
     *
     * @param VehicleJourneyRequest $request
     * @return CachedData
     * @throws Exception
     */
    public function getVehicleJourneyData(VehicleJourneyRequest $request)
    {
        return $this->getCacheOrUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshVehicleJourneyData($request);
        });
    }

    /**
     * @param VehicleJourneyRequest $request
     * @return bool|string
     * @throws Exception
     */
    protected function getFreshVehicleJourneyData(VehicleJourneyRequest $request): string|bool
    {
        $gtfsTripExtractor = new GtfsTripStartEndExtractor();
        $vehicleWithOriginAndDestination = $gtfsTripExtractor->getVehicleWithOriginAndDestination($request->getVehicleId(), $request->getDateTime());
        if ($vehicleWithOriginAndDestination === false) {
            throw new Exception('Vehicle not found in GTFS data', 404);
        }
        $journeyDetailRef = $this->getJourneyDetailRef($gtfsTripExtractor, $request, $vehicleWithOriginAndDestination);
        if ($journeyDetailRef === null) {
            throw new Exception('Vehicle not found', 404);
        }
        return $this->getJourneyDetailResponse($request, $journeyDetailRef);
    }

    /**
     * @param GtfsTripStartEndExtractor       $gtfsTripExtractor
     * @param VehicleJourneyRequest           $request
     * @param JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return string|null
     * @throws Exception
     */
    private function getJourneyDetailRef(GtfsTripStartEndExtractor $gtfsTripExtractor,
        VehicleJourneyRequest $request,
        JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
    ): ?string
    {
        $journeyDetailRef = self::findVehicleJourneyRefBetweenStops($request, $vehicleWithOriginAndDestination);
        # If false, the journey might have been partially cancelled. Try to find it by searching for parts of the journey
        if ($journeyDetailRef === false) {
            $cacheKey = "getJourneyDetailRefAlt|{$vehicleWithOriginAndDestination->getJourneyNumber()}|{$request->getDateTime()->format('Ymd')}";
            $journeyDetailRef = $this->getCacheOrUpdate($cacheKey,
                function () use ($gtfsTripExtractor, $request, $vehicleWithOriginAndDestination) {
                    return $this->getJourneyDetailRefAlt($gtfsTripExtractor, $request, $vehicleWithOriginAndDestination);
                },
                // Cache for 4 hours
                ttl: 3600 * 4);
        }
        # If no reference has been found at this stage, fail
        if ($journeyDetailRef === false) {
            throw new Exception('Vehicle not found', 404);
        }
        return $journeyDetailRef;
    }

    /**
     * @param VehicleJourneyRequest           $request
     * @param JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return string|false
     */
    private function findVehicleJourneyRefBetweenStops(VehicleJourneyRequest $request,
        JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
    ): string|false
    {
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey';

        $formattedDateStr = $request->getDateTime()->format('Y-m-d');

        $vehicleName = $vehicleWithOriginAndDestination->getJourneyType() . $vehicleWithOriginAndDestination->getJourneyNumber();
        $parameters = [
            'trainFilter' => $vehicleName, // type + number, type is required!
            'originExtId' => $vehicleWithOriginAndDestination->getOriginStopId(),
            'destExtId'   => $vehicleWithOriginAndDestination->getDestinationStopId(),
            'date'        => $formattedDateStr,
            'lang'        => $request->getLanguage()
        ];
        $url = $url . '?' . http_build_query($parameters, '', null,);

        $journeyResponse = $this->makeApiCallToMobileRivApi($url, $parameters);

        $journeyResponse = json_decode($journeyResponse, true);

        if ($journeyResponse === null || !key_exists('Trip', $journeyResponse)) {
            return false;
        }
        return $journeyResponse['Trip'][0]['LegList']['Leg'][0]['JourneyDetailRef']['ref'];
    }

    /**
     * Get the journey detail reference by trying alternative origin-destination stretches, to cope with cancelled origin/destination stops
     * @param GtfsTripStartEndExtractor       $gtfsTripExtractor
     * @param VehicleJourneyRequest           $request
     * @param JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return string|bool
     * @throws Exception
     */
    private function getJourneyDetailRefAlt(GtfsTripStartEndExtractor $gtfsTripExtractor,
        VehicleJourneyRequest $request,
        JourneyWithOriginAndDestination $vehicleWithOriginAndDestination
    ): string|bool
    {
        $alternativeOriginDestinations = $gtfsTripExtractor->getAlternativeVehicleWithOriginAndDestination(
            $vehicleWithOriginAndDestination
        );
        // Assume the first and last stop are cancelled, since the normal origin-destination search did not return results
        // This saves 2 requests and should not make a difference.
        $i = 1;
        $journeyRef = false;
        while ($journeyRef === false && $i < count($alternativeOriginDestinations) - 1) {
            Log::debug("Searching for vehicle {$request->getVehicleId()} using alternative segments, $i");
            $altVehicleWithOriginAndDestination = $alternativeOriginDestinations[$i++];
            $journeyRef = $this->findVehicleJourneyRefBetweenStops($request, $altVehicleWithOriginAndDestination);
        }
        return $journeyRef;
    }

    /**
     * @param VehicleJourneyRequest $request
     * @param string                $journeyDetailRef
     * @return bool|string
     */
    private function getJourneyDetailResponse(VehicleJourneyRequest $request, string $journeyDetailRef): string|bool
    {
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey/detail';
        $parameters = [
            'id'   => $journeyDetailRef,
            'lang' => $request->getLanguage()
        ];
        return $this->makeApiCallToMobileRivApi($url, $parameters);
    }


    /**
     * @param string $url
     * @param array  $parameters
     * @return string
     */
    private function makeApiCallToMobileRivApi(string $url, array $parameters): string
    {
        $response = $this->curlProxy->get($url, $parameters, ['x-api-key: ' . getenv('NMBS_RIV_API_KEY')]);
        return $response->getResponseBody();
    }

}
