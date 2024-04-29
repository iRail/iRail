<?php

namespace Irail\Repositories\Riv;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Irail\Exceptions\Internal\GtfsVehicleNotFoundException;
use Irail\Exceptions\Upstream\UpstreamRateLimitException;
use Irail\Exceptions\Upstream\UpstreamServerTimeoutException;
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
use Irail\Util\InMemoryMetrics;
use Irail\Util\VehicleIdTools;
use Psr\Cache\InvalidArgumentException;

class NmbsRivRawDataRepository
{
    use Cache;
    use BasedOnHafas;

    private StationsRepository $stationsRepository;
    private CurlProxy $curlProxy;

    # The maximum number of outgoing requests per minute towards NMBS
    private int $rateLimit;

    /**
     * @param StationsRepository $stationsRepository
     */
    public function __construct(StationsRepository $stationsRepository, CurlProxy $curlProxy)
    {
        $this->stationsRepository = $stationsRepository;
        $this->curlProxy = $curlProxy;
        $this->setCachePrefix('NMBS');
        $this->rateLimit = env('NMBS_RIV_RATE_LIMIT_PER_MINUTE', 10);
    }

    /**
     * A cache key for the counter in which requests for a given minute are counted.
     * @param null $time The time for which to count. Default now.
     * @return string The cache key to obtain the current request rate from cache.
     */
    public function getRequestRateKey($time = null): string
    {
        if ($time == null) {
            $time = Carbon::now();
        }
        return 'RequestRate-' . $time->format('Y-m-d_H-i');
    }

    /**
     * Get the request rate based on a given cache key.
     * @param string $key the cache key for which the request rate should be looked up, defaults to the cache key for "now".
     * @return int The number of requests in a minute.
     * @throws InvalidArgumentException
     */
    public function getRequestRate($key = null): int
    {
        if ($key == null) {
            // Get the cache key of the current "bucket" in which requests are counted
            $key = $this->getRequestRateKey();
        }
        $cachedData = $this->getCachedObject($key);
        if ($cachedData == null) {
            return 0;
        }
        return $cachedData->getValue();
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
        $station = $this->stationsRepository->getStationById($request->getStationId()); // This ensures the station exists, before we send a request
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
        }, 30);
    }

    /**
     * @param JourneyPlanningRequest $request
     * @return string The JSON data returned by the HAFAS system
     */
    private function getFreshRouteplanningData(JourneyPlanningRequest $request): string
    {
        // This ensures the station exists, before we send a request
        $origin = $this->stationsRepository->getStationById($request->getOriginStationId());
        $destination = $this->stationsRepository->getStationById($request->getDestinationStationId());
        $url = 'https://mobile-riv.api.belgianrail.be/riv/v1.0/journey';

        $typeOfTransportCode = NmbsRivApiTransportTypeFilter::forTypeOfTransportFilter(
            $origin->getId(),
            $destination->getId(),
            $request->getTypesOfTransport());

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
        return $this->makeApiCallToMobileRivApi($url, $parameters);
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
        return $this->getCacheOrUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshVehicleJourneyData($request);
        });
    }

    /**
     * Get the composition for a vehicle.
     *
     * @param Vehicle                         $vehicle
     * @param JourneyWithOriginAndDestination $originAndDestination
     * @return CachedData
     */
    public function getVehicleCompositionData(Vehicle $vehicle, JourneyWithOriginAndDestination $originAndDestination): CachedData
    {
        return $this->getCacheOrUpdate("composition|{$vehicle->getNumber()}|{$vehicle->getJourneyStartDate()->format('Ymd')}",
            function () use ($vehicle, $originAndDestination) {
                return $this->getVehicleCompositionResponse($vehicle, $originAndDestination);
            });
    }

    /**
     * @param VehicleJourneyRequest $request
     * @return bool|string
     * @throws GtfsVehicleNotFoundException
     */
    protected function getFreshVehicleJourneyData(VehicleJourneyRequest $request): string|bool
    {
        $announcedJourneyNumber = VehicleIdTools::extractTrainNumber($request->getVehicleId());
        $cacheKey = "journeyDetailRef|{$announcedJourneyNumber}|{$request->getDateTime()->format('Ymd')}";

        $cachedJourneyDetailRef = $this->getCacheOrUpdate($cacheKey,
            function () use ($request) {
                try {
                    return $this->getJourneyDetailRef($request);
                } catch (GtfsVehicleNotFoundException $e) {
                    return false;
                }
            }, 4 * 3600
        );

        if ($cachedJourneyDetailRef->getValue() === false) {
            // If an exception was cached, throw it
            throw new GtfsVehicleNotFoundException($request->getVehicleId());
        }

        $journeyDetailRef = $cachedJourneyDetailRef->getValue();
        return $this->getJourneyDetailResponse($request, $journeyDetailRef);
    }

    /**
     * @param VehicleJourneyRequest $request
     * @throw
     * @return string
     */
    private function getJourneyDetailRef(
        VehicleJourneyRequest $request
    ): string {
        /** @var GtfsTripStartEndExtractor $gtfsTripExtractor */
        $gtfsTripExtractor = App::make(GtfsTripStartEndExtractor::class);
        $vehicleWithOriginAndDestination = $gtfsTripExtractor->getVehicleWithOriginAndDestination($request->getVehicleId(), $request->getDateTime());
        if ($vehicleWithOriginAndDestination === false) {
            throw new GtfsVehicleNotFoundException($request->getVehicleId());
        }

        $journeyDetailRef = self::findVehicleJourneyRefBetweenStops($request, $vehicleWithOriginAndDestination);
        # If false, the journey might have been partially cancelled. Try to find it by searching for parts of the journey
        if ($journeyDetailRef === false) {
            $journeyDetailRef = $this->getJourneyDetailRefAlt($gtfsTripExtractor, $request, $vehicleWithOriginAndDestination);
        }

        Log::debug("Found journey detail ref: '{$journeyDetailRef}' between {$vehicleWithOriginAndDestination->getOriginStopId()} and destination {$vehicleWithOriginAndDestination->getdestinationStopId()}");
        # If no reference has been found at this stage, fail
        if ($journeyDetailRef === false) {
            throw new GtfsVehicleNotFoundException($request->getVehicleId());
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
     */
    private function getJourneyDetailRefAlt(
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
     * @param Vehicle                         $vehicle
     * @param JourneyWithOriginAndDestination $journeyWithOriginAndDestination
     * @return bool|string
     */
    private function getVehicleCompositionResponse(Vehicle $vehicle, JourneyWithOriginAndDestination $journeyWithOriginAndDestination): string|bool
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
        return $this->makeApiCallToMobileRivApi($url, $parameters);
    }


    /**
     * @param string $url
     * @param array  $parameters
     * @return string
     */
    private function makeApiCallToMobileRivApi(string $url, array $parameters): string
    {
        $response = RateLimiter::attempt(
            'riv-request',
            $this->rateLimit,
            function () use ($url, $parameters) {
                InMemoryMetrics::countRivCall();
                return $this->curlProxy->get($url, $parameters, ['x-api-key: ' . getenv('NMBS_RIV_API_KEY')]);
            },
            60 // 60 seconds buckets, i.e. rate limiting per minute
        );
        if ($response === false) {
            $currentRequestRate = $this->rateLimit - RateLimiter::remaining('riv-request', $this->rateLimit);
            $message = "Current request rate towards NMBS is $currentRequestRate requests per minute. "
                . "Outgoing request blocked due to rate limiting configured at {$this->rateLimit} requests per minute";
            Log::error($message);
            throw new UpstreamRateLimitException($message);
        }
        $response = $response->getResponseBody();
        if (str_starts_with($response, '{"exception":"Hacon response time exceeded the defined timeout')) {
            // TODO: error handling should be grouped somewhere together with JSON parsing.
            throw new UpstreamServerTimeoutException('The upstream server encountered a timeout while loading data. Please try again later.');
        }
        return $response;
    }

}
