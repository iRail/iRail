<?php

namespace Irail\Data\Nmbs\Repositories;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use Irail\Data\Nmbs\HafasDatasource;
use Irail\Models\CachedData;
use Irail\Models\DepartureArrivalMode;
use Irail\Models\Requests\ConnectionsRequest;
use Irail\Models\Requests\LiveboardRequest;
use Irail\Models\Requests\TimeSelection;
use Irail\Models\Requests\TypeOfTransportFilter;
use Irail\Models\Requests\VehicleJourneyRequest;
use Irail\Traits\Cache;

class RawDataRepository
{
    use Cache;
    use HafasDatasource;

    const CURL_HEADER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36';
    const CURL_HEADER_REFERRER = 'http://api.irail.be/';
    const CURL_TIMEOUT = 30;

    const RIV_API_KEY = 'IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl';

    private StationsRepository $stationsRepository;

    /**
     * @param StationsRepository $stationsRepository
     */
    public function __construct(StationsRepository $stationsRepository)
    {
        $this->stationsRepository = $stationsRepository;
        $this->setCachePrefix('NMBS');
    }

    /**
     * @param LiveboardRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getLiveboardData(LiveboardRequest $request): CachedData
    {
        return $this->getCacheWithDefaultCacheUpdate($request->getCacheId(), function () use ($request) {
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

        $url = "https://mobile-riv.api.belgianrail.be/api/v1.0/dacs";
        $formattedDateTimeStr = $request->getDateTime()->format('Y-m-d H:i:s');

        $queryType = ($request->getDepartureArrivalMode() == DepartureArrivalMode::MODE_ARRIVAL)
            ? 'ArrivalsApp'
            : 'DeparturesApp';

        $parameters = [
            'query'   => $queryType, // include intermediate stops along the way
            'UicCode' => substr($hafasStationId, 2),
            // 'FromDate' => $formattedDateTimeStr, // requires date in 'yyyy-mm-dd hh:mm:ss' format TODO: figure out how this works
            'Count'   => 100, // include intermediate stops along the way
            // language is not passed, responses contain both Dutch and French destinations
        ];
        return $this->makeApiCallToMobileRivApi($url, $parameters);

    }

    /**
     * @param ConnectionsRequest $request
     * @return CachedData the data, along with information about its age and validity
     */
    public function getRoutePlanningData(ConnectionsRequest $request): CachedData
    {
        return $this->getCacheWithDefaultCacheUpdate($request->getCacheId(), function () use ($request) {
            return $this->getFreshRouteplanningData($request);
        });
    }

    /**
     * @param ConnectionsRequest $request
     * @return string The JSON data returned by the HAFAS system
     */
    private function getFreshRouteplanningData(
        ConnectionsRequest $request
    ): string
    {
        $url = "https://mobile-riv.api.belgianrail.be/riv/v1.0/journey";

        $typeOfTransportCode = self::getTypeOfTransportBitcode(
            $request->getOriginStationId(),
            $request->getDestinationStationId(),
            $request->getTypesOfTransport());

        $formattedDateStr = $request->getDateTime()->format('Y-m-d');
        $formattedTimeStr = $request->getDateTime()->format('H:i:s');

        $parameters = [
            // 'trainFilter'      => 'S206466',// TODO: figure out valid values and meaning
            'originExtId'      => substr($request->getOriginStationId(), 2),
            'destExtId'        => substr($request->getDestinationStationId(), 2),
            'date'             => $formattedDateStr, // requires date in yyyy-mm-dd format
            'time'             => $formattedTimeStr, // requires time in hh:mm:ss format
            'lang'             => $request->getLanguage(),
            'passlist'         => true, // include intermediate stops along the way
            'searchForArrival' => ($request->getTimeSelection() == TimeSelection::ARRIVAL), // include intermediate stops along the way
            'numF'             => 6, // include intermediate stops along the way
            'products'         => $typeOfTransportCode
        ];
        return $this->makeApiCallToMobileRivApi($url, $parameters);
    }


    private static function getTypeOfTransportBitcode(string $fromStationId, string $toStationId, TypeOfTransportFilter $typeOfTransportFilter): NmbsRivApiTransportTypeFilter
    {
        // Convert the type of transport key to a bitcode needed in the request payload
        // Automatic is the default type, which prevents that local trains aren't shown because a high-speed train provides a faster connection
        if ($typeOfTransportFilter == TypeOfTransportFilter::AUTOMATIC) {
            // 2 national stations: no international trains
            // Internation station: all
            if (str_starts_with($fromStationId, '0088') && str_starts_with($toStationId, '0088')) {
                return NmbsRivApiTransportTypeFilter::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;
            } else {
                return NmbsRivApiTransportTypeFilter::TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
            }
        } else if ($typeOfTransportFilter == TypeOfTransportFilter::NO_INTERNATIONAL_TRAINS) {
            return NmbsRivApiTransportTypeFilter::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;
        } else if ($typeOfTransportFilter == TypeOfTransportFilter::ALL_TRAINS) {
            return NmbsRivApiTransportTypeFilter::TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
        }
        // All trains is the default
        return NmbsRivApiTransportTypeFilter::TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
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
        return $this->getCacheWithDefaultCacheUpdate($request->getCacheId(), function () use ($request) {
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
        // TODO: re-implement
        return false;
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @return bool|string
     */
    public function makeApiCallToMobileRivApi(string $url, array $parameters): string|bool
    {
        $url = $url . '?' . http_build_query($parameters, "", null,);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::CURL_HEADER_USER_AGENT);
        curl_setopt($ch, CURLOPT_REFERER, self::CURL_HEADER_REFERRER);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . self::RIV_API_KEY]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }


}
