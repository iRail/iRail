<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */

namespace Irail\Data\Nmbs;

use DateTime;
use Exception;
use Irail\Data\Nmbs\Models\hafas\HafasVehicle;
use Irail\Data\Nmbs\Models\Station;
use Irail\Data\Nmbs\Repositories\RawDataRepository;
use Irail\Data\Nmbs\Repositories\StationsRepository;
use Irail\Data\Nmbs\Tools\HafasCommon;
use Irail\Data\Nmbs\Tools\VehicleIdTools;
use Irail\Models\CachedData;
use Irail\Models\DepartureArrival;
use Irail\Models\Occupancy;
use Irail\Models\PlatformInfo;
use Irail\Models\Requests\LiveboardRequest;
use Irail\Models\Result\LiveboardResult;
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
        // Get all those lists so we can read the actual data.
        $locationDefinitions = $this->parseLocationDefinitions($json);
        $vehicleDefinitions = $this->parseVehicleDefinitions($json);
        $remarkDefinitions = $this->parseRemarkDefinitions($json);
        $alertDefinitions = $this->parseAlertDefinitions($json);

        // Now we'll actually read the departures/arrivals information.
        $currentStation = $this->stationsRepository->getStationById($locationDefinitions[0]->id);
        if ($currentStation == null) {
            throw new Exception("Failed to match station id {$locationDefinitions[0]->id} with a known station",
                500);
        }
        $stopsAtStation = $json['svcResL'][0]['res']['jnyL'];
        $departuresOrArrivals = [];
        foreach ($stopsAtStation as $stop) {
            $departuresOrArrivals[] = $this->parseStopAtStation($currentStation, $stop, $vehicleDefinitions);
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
     * @param StationInfo    $currentStation
     * @param mixed          $stop
     * @param HafasVehicle[] $vehicleDefinitions
     * @return DepartureArrival
     * @throws Exception
     */
    private function parseStopAtStation(StationInfo $currentStation, array $stop, array $vehicleDefinitions): DepartureArrival
    {

        // The date of this departure, in Ymd format
        $date = $stop['date'];

        [$scheduledTime, $delay] = $this->parseScheduledTimeAndDelay($stop['stbStop'], $date);

        // parse information about which platform this train will depart from/arrive to.
        [$platform, $isPlatformNormal] = self::parsePlatformData($stop);

        // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
        // DepartureCanceled gives information if this stop has been canceled.
        [$stopCanceled, $left] = self::determineCanceledAndLeftStatus($stop);

        $isExtraTrain = (key_exists('status', $stop) && $stop['status'] == 'A');
        $directionStation = $this->stationsRepository->findStationByName($stop['dirTxt']);

        $stopAtStation = new DepartureArrival();
        $stopAtStation->setStation($currentStation);
        $stopAtStation->setHeadsign($stop['dirTxt']);
        $stopAtStation->setDirection([$directionStation]);

        $isDeparture = key_exists('dProdX', $stop['stbStop']);
        if ($isDeparture) {
            $stopAtStation->setScheduledDepartureTime($scheduledTime)->setDepartureDelay($delay);
            $hafasVehicle = $vehicleDefinitions[$stop['stbStop']['dProdX']];
        } else {
            $stopAtStation->setScheduledArrivalTime($scheduledTime)->setArrivalDelay($delay);
            $hafasVehicle = $vehicleDefinitions[$stop['stbStop']['aProdX']];
        }

        $stopAtStation->setPlatform(new PlatformInfo($directionStation->getId(), $platform, $isPlatformNormal));
        $stopAtStation->setHasArrived($left)->setHasLeft($left)->setIsExtraStop($isExtraTrain)->setIsCanceled($stopCanceled);
        $stopAtStation->setVehicle(
            new Vehicle(
                $hafasVehicle->getUri(),
                $hafasVehicle->num,
                $hafasVehicle->name,
                VehicleIdTools::extractTrainType($hafasVehicle->name),
                $hafasVehicle->num)
        );

        // Include partiallyCanceled, but don't include canceled.
        // PartiallyCanceled might mean the next 3 stations are canceled while this station isn't.
        // Canceled means all stations are canceled, including this one
        // TODO: enable partially canceled as soon as it's reliable, ATM it still marks trains which aren't partially canceled at all
        // $stopAtStation->partiallyCanceled = $partiallyCanceled;
        $stopAtStation->setUri($this->buildDepartureUri($currentStation, $scheduledTime, $hafasVehicle->name));

        // Add occupancy data, if available
        $stopAtStation->setOccupancy(self::getOccupancy($currentStation, $stopAtStation, $date));
        return $stopAtStation;
    }

    /**
     * Parse the platform name, and whether or not there has been a change in platforms.
     *
     * @param $stop
     * @return array
     */
    private static function parsePlatformData($stop): array
    {
        // Depending on whether we're showing departures or arrivals, we should load different fields
        if (key_exists('dProdX', $stop['stbStop'])) {
            // Departures
            if (key_exists('dPlatfR', $stop['stbStop'])) {
                $platform = $stop['stbStop']['dPlatfR'];
                $isPlatformNormal = false;
            } else if (key_exists('dPlatfS', $stop['stbStop'])) {
                $platform = $stop['stbStop']['dPlatfS'];
                $isPlatformNormal = true;
            } else {
                // TODO: is this what we want when we don't know the platform?
                $platform = "?";
                $isPlatformNormal = true;
            }
        } else {
            // Arrivals
            if (key_exists('aPlatfR', $stop['stbStop'])) {
                $platform = $stop['stbStop']['aPlatfR'];
                $isPlatformNormal = false;
            } else if (key_exists('aPlatfS', $stop['stbStop'])) {
                $platform = $stop['stbStop']['aPlatfS'];
                $isPlatformNormal = true;
            } else {
                // TODO: is this what we want when we don't know the platform?
                $platform = "?";
                $isPlatformNormal = true;
            }
        }
        return [$platform, $isPlatformNormal];
    }


    /**
     * Determine whether or not the train has been canceled, or has left the station.
     *
     * @param $stop
     * @return array
     */
    private static function determineCanceledAndLeftStatus($stop): array
    {
        $partiallyCanceled = 0;
        $stopCanceled = 0;
        if (key_exists('isCncl', $stop)) {
            $stopCanceled = $stop['isCncl'];
        }
        if (key_exists('isPartCncl', $stop)) {
            $partiallyCanceled = $stop['isPartCncl'];
        }
        if ($stopCanceled) {
            $partiallyCanceled = 1; // Completely canceled is a special case of partially canceled
        }

        $left = 0;
        // Again we need to distinguish departures and arrivals
        if (key_exists('dProgType', $stop['stbStop'])) {
            if ($stop['stbStop']['dProgType'] == 'REPORTED') {
                $left = 1;
            }
            if (key_exists('dCncl', $stop['stbStop'])) {
                $stopCanceled = $stop['stbStop']['dCncl'];
            }
        } else if (key_exists('aProgType', $stop['stbStop'])) {
            if ($stop['stbStop']['aProgType'] == 'REPORTED') {
                $left = 1;
            }
            if (key_exists('aCncl', $stop['stbStop'])) {
                $stopCanceled = $stop['stbStop']['aCncl'];
            }
        }
        return [$stopCanceled, $left];
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param Station          $currentStation
     * @param DepartureArrival $stopAtStation
     * @param                  $date
     * @return DepartureArrival
     */
    private function getOccupancy(StationInfo $currentStation, DepartureArrival $stopAtStation, $date): Occupancy
    {
        // TODO: implement
        return new Occupancy();
    }

    /**
     * @param StationInfo $currentStation
     * @param int         $departureTime
     * @param string      $vehicleId
     * @return string
     */
    private function buildDepartureUri(StationInfo $currentStation, DateTime $departureTime, string $vehicleId): string
    {
        return 'http://irail.be/connections/' . substr($currentStation->getId(), 2)
            . '/' . $departureTime->format('YMd') . '/' . $vehicleId;
    }

    /**
     * @param       $stbStop
     * @param mixed $date
     * @return array
     */
    private function parseScheduledTimeAndDelay($stbStop, mixed $date): array
    {
// parse the scheduled time of arrival/departure and the vehicle (which is returned as a number to look up in the vehicle definitions list)
        // Depending on whether we're showing departures or arrivals, we should load different fields
        if (key_exists('dProdX', $stbStop)) {
            // Departures
            $scheduledTime = DateTime::createFromFormat("Ymd His", $date . ' ' . $stbStop['dTimeS']);
            if (key_exists('dTimeR', $stbStop)) {
                $estimatedTime = DateTime::createFromFormat("Ymd His", $date . ' ' . $stbStop['dTimeR']);
            } else {
                $estimatedTime = $scheduledTime;
            }
        } else {
            // Arrivals
            $scheduledTime = DateTime::createFromFormat("Ymd His", $date . ' ' . $stbStop['aTimeS']);
            if (key_exists('aTimeR', $stbStop)) {
                $estimatedTime = DateTime::createFromFormat("Ymd His", $date . ' ' . $stbStop['aTimeR']);
            } else {
                $estimatedTime = $scheduledTime;
            }
        }
        $delay = $estimatedTime->getTimestamp() - $scheduledTime->getTimestamp();
        return [$scheduledTime, $delay];
    }


}
