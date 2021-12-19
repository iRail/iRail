<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 */

namespace Irail\Data\Nmbs;

use Carbon\Carbon;
use DateTime;
use Exception;
use Irail\Data\Nmbs\Models\hafas\HafasResponseContext;
use Irail\Data\Nmbs\Models\hafas\HafasVehicle;
use Irail\Data\Nmbs\Repositories\RawDataRepository;
use Irail\Data\Nmbs\Repositories\StationsRepository;
use Irail\Models\CachedData;
use Irail\Models\Occupancy;
use Irail\Models\Requests\LiveboardRequest;
use Irail\Models\Result\LiveboardResult;
use Irail\Models\StationBoardEntry;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;

class LiveboardDatasource
{
    use  HafasDatasource;

    private StationsRepository $stationsRepository;
    private RawDataRepository $rawDataRepository;

    public function __construct(StationsRepository $stationsRepository, RawDataRepository $rawDataRepository)
    {
        $this->stationsRepository = $stationsRepository;
        $this->rawDataRepository = $rawDataRepository;
    }

    /**
     * This is the entry point for the data fetching and transformation.
     *
     * @param LiveboardRequest $request
     * @return LiveboardResult
     * @throws Exception
     */
    public function getLiveboard(LiveboardRequest $request): LiveboardResult
    {
        $rawData = $this->rawDataRepository->getLiveboardData($request);
        $this->stationsRepository->setLocalizedLanguage($request->getLanguage());
        return $this->parseNmbsRawData($rawData);
    }

    private function parseNmbsRawData(CachedData $cachedRawData): LiveboardResult
    {
        $rawData = $cachedRawData->getValue();
        $json = $this->decodeAndVerifyResponse($rawData);

        // A Hafas API response contains all locations, trains, ... in separate lists to prevent duplicate data.
        // Get a context object containing all those objects
        $context = HafasResponseContext::fromJson($json);

        // Now we'll actually read the departures/arrivals information.
        $currentStation = $this->stationsRepository->getStationById($this->hafasIdToIrailId($context->getLocations()[0]->getExtId()));
        if ($currentStation == null) {
            throw new Exception("Failed to match station id {$context->getLocations()[0]->getExtId()} with a known station",
                500);
        }
        $stopsAtStation = $json['svcResL'][0]['res']['jnyL'];
        $departuresOrArrivals = [];
        foreach ($stopsAtStation as $stop) {
            $departuresOrArrivals[] = $this->parseStopAtStation($currentStation, $stop, $context);
        }

        return new LiveboardResult($cachedRawData->getCreatedAt(), $currentStation, $departuresOrArrivals);
    }



    /**
     * Parse a HAFAS stop, for example
     * {
     *   "jid": "1|586|1|80|26102017",
     *   "date": "20171026",
     *   "prodX": 0,
     *   "dirTxt": "Eeklo",
     *   "status": "P",
     *   "isRchbl": true,
     *   "stbStop": {
     *      "locX": 0,
     *      "idx": 7,
     *      "dProdX": 0,
     *      "dPlatfS": "2",
     *      "dPlatfR": "1",
     *      "dTimeS": "141200",
     *      "dTimeR": "141200",
     *      "dProgType": "PROGNOSED"
     *   }
     * }
     * @param StationInfo          $currentStation
     * @param mixed                $stop
     * @param HafasResponseContext $context
     * @return StationBoardEntry
     * @throws Exception
     */
    private function parseStopAtStation(StationInfo $currentStation, array $stop, HafasResponseContext $context): StationBoardEntry
    {
        // The date of this departure, in Ymd format
        $date = $stop['date'];
        $stbStop = $stop['stbStop'];
        $isDeparture = key_exists('dProdX', $stbStop);
        $isArrival = key_exists('dProdX', $stbStop);

        if (!($isDeparture || $isArrival)) {
            throw new Exception("StationBoard contains an entry without a departure or arrival date");
        }

        if ($isDeparture) {
            // Departures
            $scheduledTime = key_exists('dTimeS', $stbStop) ? Carbon::createFromFormat("Ymd His", $date . ' ' . $stbStop['dTimeS']) : null;
            $estimatedTime = key_exists('dTimeR', $stbStop) ? Carbon::createFromFormat("Ymd His", $date . ' ' . $stbStop['dTimeR']) : $scheduledTime;
            $platform = $this->parsePlatform($currentStation, $stop, 'dPlatfS', 'dPlatfR');
            $isReported = (key_exists('dProgType', $stbStop) && $stbStop['dProgType'] == 'REPORTED');
            $isCanceled = (key_exists('dCncl', $stbStop) && $stbStop['dCncl']);
            $vehicle = $context->getVehicle($stbStop['dProdX']);

            $uri = $this->buildDepartureUri($currentStation, $scheduledTime, $vehicle->getDisplayName());
            // Add occupancy data, if available
            $occupancy = self::getOccupancy($currentStation, $vehicle, $date);
        } else {

            // Arrivals
            $scheduledTime = key_exists('aTimeS', $stbStop) ? Carbon::createFromFormat("Ymd His", $date . ' ' . $stbStop['aTimeS']) : null;
            $estimatedTime = key_exists('aTimeR', $stbStop) ? Carbon::createFromFormat("Ymd His", $date . ' ' . $stbStop['aTimeR']) : $scheduledTime;
            $platform = $this->parsePlatform($currentStation, $stop, 'aPlatfS', 'aPlatfR');
            $isReported = (key_exists('aProgType', $stbStop) && $stbStop['aProgType'] == 'REPORTED');
            $isCanceled = (key_exists('aCncl', $stbStop) && $stbStop['aCncl']);
            $vehicle = $context->getVehicle($stbStop['aProdX']);

            $uri = null;
            $occupancy = null;
        }
        $delay = $estimatedTime->diffInRealSeconds($scheduledTime);

        // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
        // DepartureCanceled gives information if this stop has been canceled.

        $partiallyCanceled = 0;
        $stopCanceled = 0;
        if (key_exists('isCncl', $stop)) {
            $stopCanceled = $stop['isCncl'];
            $partiallyCanceled = true;
        }
        if (key_exists('isPartCncl', $stop)) {
            $partiallyCanceled = $stop['isPartCncl'];
        }

        $isExtraTrain = (key_exists('status', $stop) && $stop['status'] == 'A');
        $directionStation = $this->stationsRepository->findStationByName($stop['dirTxt']);

        $stationBoardEntry = new StationBoardEntry();

        $stationBoardEntry->setStation($currentStation);
        $stationBoardEntry->setHeadsign($stop['dirTxt']);
        $stationBoardEntry->setDirection([$directionStation]);
        $stationBoardEntry->setScheduledDateTime($scheduledTime)->setDelay($delay);

        $stationBoardEntry->setPlatform($platform);
        $stationBoardEntry->setIsReported($isReported)->setIsExtra($isExtraTrain)->setIsCancelled($stopCanceled);
        $stationBoardEntry->setVehicle(
            new Vehicle(
                $vehicle->getUri(),
                $vehicle->getNumber(),
                $vehicle->getDisplayName(),
                $vehicle->getType(),
                $vehicle->getNumber())
        );

        $stationBoardEntry->setUri($uri);
        $stationBoardEntry->setOccupany($occupancy);
        return $stationBoardEntry;
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param StationInfo          $currentStation
     * @param HafasVehicle              $vehicle
     * @param                      $date
     * @return Occupancy
     */
    private function getOccupancy(StationInfo $currentStation, HafasVehicle $vehicle, $date): Occupancy
    {
        // TODO: implement
        return new Occupancy();
    }

    /**
     * @param StationInfo $currentStation
     * @param DateTime    $departureTime
     * @param string      $vehicleId
     * @return string
     */
    private function buildDepartureUri(StationInfo $currentStation, DateTime $departureTime, string $vehicleId): string
    {
        return 'http://irail.be/connections/' . substr($currentStation->getId(), 2)
            . '/' . $departureTime->format('YMd') . '/' . $vehicleId;
    }

}
