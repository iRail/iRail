<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 * This will fetch all vehicledata for the NMBS.
 *   * fillDataRoot will fill the entire dataroot with vehicleinformation
 */

namespace Irail\Data\Nmbs;

use DateTime;
use Exception;
use Irail\api\data\DataRoot;
use Irail\Data\Nmbs\Models\Alert;
use Irail\Data\Nmbs\Models\hafas\HafasVehicle;
use Irail\Data\Nmbs\Models\Platform;
use Irail\Data\Nmbs\Models\Stop;
use Irail\Data\Nmbs\Models\VehicleInfo;
use Irail\Data\Nmbs\Repositories\RawDataRepository;
use Irail\Data\Nmbs\Repositories\StationsRepository;
use Irail\Data\Nmbs\Tools\HafasCommon;
use Irail\Data\Nmbs\Tools\Tools;
use Irail\Http\Requests\VehicleinformationRequest;
use Irail\Legacy\Occupancy\OccupancyOperations;
use Irail\Models\CachedData;
use Irail\Models\DepartureArrival;
use Irail\Models\Requests\LiveboardRequest;
use Irail\Models\Requests\VehicleJourneyRequest;
use Irail\Models\Result\LiveboardResult;
use Irail\Models\Result\VehicleJourneyResult;

class VehicleDatasource
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
    public function getDatedVehicleJourney(VehicleJourneyRequest $request): VehicleJourneyResult
    {
        $rawData = $this->rawDataRepository->getVehicleJourneyData($request);
        $this->stationsRepository->setLocalizedLanguage($request->getLanguage());
        return $this->parseNmbsRawVehicleJourney($rawData);
    }

    /**
     * @param CachedData $cachedRawData
     * @return VehicleJourneyResult
     * @throws Exception
     */
    private function parseNmbsRawVehicleJourney(CachedData $cachedRawData): VehicleJourneyResult
    {
        $rawData = $cachedRawData->getValue();
        $json = $this->decodeAndVerifyResponse($rawData);

        $vehicleInfo = $this->parseVehicleInfo($json);
        $stops = $this->parseVehicleStops($json);
        $alerts = []; // TODO: implement
        return new VehicleJourneyResult($cachedRawData->getCreatedAt(), $vehicleInfo, $stops, $alerts);
    }

    /**
     * @param string $serverData
     * @param string $lang
     * @param $occupancyArr
     * @return Stop[]
     * @throws Exception
     */
    protected static function getStops(string $serverData, string $lang, $occupancyArr)
    {
        $json = json_decode($serverData, true);
        $locationDefinitions = HafasCommon::parseLocationDefinitions($json);
        $vehicleDefinitions = HafasCommon::parseVehicleDefinitions($json);

        $stops = [];
        $dateOfFirstDeparture = $json['svcResL'][0]['res']['journey']['date'];
        $dateOfFirstDeparture = DateTime::createFromFormat('Ymd', $dateOfFirstDeparture);

        $stopIndex = 0;
        // TODO: pick the right train here, a train which splits has multiple parts here.
        foreach ($json['svcResL'][0]['res']['journey']['stopL'] as $rawStop) {
            $stop = self::parseVehicleStop(
                $rawStop,
                $dateOfFirstDeparture,
                $lang,
                $vehicleDefinitions,
                $locationDefinitions,
                $stopIndex == 0,
                $stopIndex == count($json['svcResL'][0]['res']['journey']['stopL']) - 1
            );

            $stops[] = $stop;
            self::addOccuppancyData($dateOfFirstDeparture, $occupancyArr, $stop);
            $stopIndex++;
        }
        // Use ArrivalDelay instead of DepartureDelay for the last stop, since DepartureDelay will always be 0 (there is no departure)
        $stops[count($stops) - 1]->delay = $stops[count($stops) - 1]->arrivalDelay;

        self::ensureTrainHasLeftPreviousStop($stops);

        return $stops;
    }

    /**
     * @param $rawStop
     * @param DateTime $firstStationDepartureDate date on which the train leaves the first station on its journey.
     * @param $lang
     * @param $vehiclesInJourney
     * @param $locationDefinitions
     * @param bool $isFirstStop
     * @param bool $isLastStop
     * @return Stop
     * @throws Exception
     */
    private static function parseVehicleStop($rawStop, DateTime $firstStationDepartureDate, string $lang, $vehiclesInJourney, $locationDefinitions, bool $isFirstStop, bool $isLastStop): Stop
    {
        /* A change in train number looks like this. The remark describes the change. Example S102063/S103863
            {
              "locX": 13,
              "idx": 13,
              "aProdX": 0,
              "aPlatfS": "7",
              "aOutR": true,
              "aTimeS": "135400",
              "aTimeR": "135400",
              "aProgType": "REPORTED",
              "dProdX": 1,
              "dPlatfS": "7",
              "dInR": true,
              "dTimeS": "135700",
              "dTimeR": "135700",
              "dProgType": "REPORTED",
              "msgListElement": [
                {
                  "type": "REM",
                  "remX": 1
                }
              ]
            },
         */
        // TODO: export remarks as they contain information about changes in the train designation.
        $firstStationDepartureDateString = $firstStationDepartureDate->format("Ymd");
        if (key_exists('dTimeR', $rawStop)) {
            $departureDelay = tools::calculateSecondsHHMMSS(
                $rawStop['dTimeR'],
                $firstStationDepartureDateString,
                $rawStop['dTimeS'],
                $firstStationDepartureDateString
            );
        } else {
            $departureDelay = 0;
        }
        if (key_exists('dTimeS', $rawStop)) {
            $departureTime = tools::transformTime($rawStop['dTimeS'], $firstStationDepartureDateString);
        } else {
            // If the train doesn't depart from here, just use the arrival time
            $departureTime = null;
        }

        if (key_exists('aTimeR', $rawStop)) {
            $arrivalDelay = tools::calculateSecondsHHMMSS(
                $rawStop['aTimeR'],
                $firstStationDepartureDateString,
                $rawStop['aTimeS'],
                $firstStationDepartureDateString
            );
        } else {
            $arrivalDelay = 0;
        }

        if (key_exists('aTimeS', $rawStop)) {
            $arrivalTime = tools::transformTime($rawStop['aTimeS'], $firstStationDepartureDateString);
        } else {
            $arrivalTime = $departureTime;
        }

        //Delay and platform changes
        if (key_exists('dPlatfR', $rawStop)) {
            $departurePlatform = $rawStop['dPlatfR'];
            $departurePlatformNormal = false;
        } elseif (key_exists('dPlatfS', $rawStop)) {
            $departurePlatform = $rawStop['dPlatfS'];
            $departurePlatformNormal = true;
        } else {
            $departurePlatform = "?";
            $departurePlatformNormal = true;
        }

        //Delay and platform changes
        if (key_exists('aPlatfR', $rawStop)) {
            $arrivalPlatform = $rawStop['aPlatfR'];
            $arrivalPlatformNormal = false;
        } elseif (key_exists('aPlatfS', $rawStop)) {
            $arrivalPlatform = $rawStop['aPlatfS'];
            $arrivalPlatformNormal = true;
        } else {
            $arrivalPlatform = "?";
            $arrivalPlatformNormal = true;
        }

        $departureCanceled = 0;
        $arrivalCanceled = 0;

        $left = 0;
        if (key_exists('dProgType', $rawStop)) {
            if ($rawStop['dProgType'] == 'REPORTED') {
                $left = 1;
            }
        }

        if (key_exists('aProgType', $rawStop)) {
            if ($rawStop['aProgType'] == 'REPORTED') {
                $arrived = 1;
            } else {
                $arrived = 0;
            }
        } else {
            $arrived = 0;
        }

        if (key_exists('dCncl', $rawStop)) {
            $departureCanceled = $rawStop['dCncl'];
        }

        if (key_exists('aCncl', $rawStop)) {
            $arrivalCanceled = $rawStop['aCncl'];
        }

        // If the train left, it also arrived
        if ($left) {
            $arrived = 1;
        }

        $station = StationsDatasource::getStationFromID($locationDefinitions[$rawStop['locX']]->id, $lang);

        $stop = new Stop();
        $stop->station = $station;

        $stop->departureDelay = $departureDelay;
        $stop->departureCanceled = $departureCanceled;
        $stop->canceled = $departureCanceled;
        $stop->scheduledDepartureTime = $departureTime;

        $stop->platform = new Platform();
        $stop->platform->name = $departurePlatform;
        $stop->platform->normal = $departurePlatformNormal;

        $stop->time = $departureTime;

        if ($isLastStop) {
            // This is the final stop, it doesnt have a departure.
            $stop->departureDelay = 0;
            $stop->departureCanceled = 0;
            $stop->canceled = $arrivalCanceled;
            $stop->scheduledDepartureTime = $arrivalTime;

            $stop->platform = new Platform();
            $stop->platform->name = $arrivalPlatform;
            $stop->platform->normal = $arrivalPlatformNormal;

            $stop->time = $arrivalTime;
        }

        $stop->scheduledArrivalTime = $arrivalTime;
        $stop->arrivalDelay = $arrivalDelay;
        $stop->arrivalCanceled = $arrivalCanceled;
        // In case the departure is canceled, the platform data needs to be retrieved from the arrival data.
        if ($departureCanceled && !$arrivalCanceled) {
            $stop->platform->name = $arrivalPlatform;
            $stop->platform->normal = $arrivalPlatformNormal;
        }

        // The final doesn't have a departure product
        if ($isLastStop) {
            // Still include the field, just leave it empty.
            $stop->departureConnection = "";
        } else {
            $rawVehicle = $vehiclesInJourney[$rawStop['dProdX']];
            $stop->departureConnection = 'http://irail.be/connections/' .
                substr(basename($stop->station->{'@id'}), 2) . '/' .
                $firstStationDepartureDateString . '/' . $rawVehicle->name;
        }

        //for backward compatibility
        $stop->delay = $departureDelay;
        $stop->arrived = $arrived;
        $stop->left = $left;
        // TODO: detect
        $stop->isExtraStop = 0;
        return $stop;
    }

    /**
     * @param DateTime $requestedDate
     * @return bool
     */
    private static function isSpitsgidsDataAvailable(DateTime $requestedDate): bool
    {
        // Determine if this date is in the spitsgids range
        $now = new DateTime();
        $daysBetweenNowAndRequest = $now->diff($requestedDate);
        $isOccupancyDate = true;
        if ($daysBetweenNowAndRequest->d > 1 && $daysBetweenNowAndRequest->invert == 0) {
            $isOccupancyDate = false;
        }
        return $isOccupancyDate;
    }

    /**
     * @param string $serverData
     * @return HafasVehicle
     * @throws Exception
     */
    private static function getVehicleDetails(string $serverData): object
    {
        $json = json_decode($serverData, true);
        HafasCommon::throwExceptionOnInvalidResponse($json);
        $vehicleDefinitions = HafasCommon::parseVehicleDefinitions($json);
        return $vehicleDefinitions[$json['svcResL'][0]['res']['journey']['prodX']];
    }

    /**
     * @param string $serverData
     * @return Alert[]
     */
    private static function getAlerts(string $serverData): array
    {
        $json = json_decode($serverData, true);
        // These are formatted already
        return HafasCommon::parseAlertDefinitions($json);
    }

    /**
     * @param DateTime $dateOfFirstDeparture  The date when the train leaves the first station on its journey.
     * @param array $occupancyArr Occuppancy data for this train
     * @param Stop $stop The stop on which occuppancy data needs to be added.
     */
    protected static function addOccuppancyData(DateTime $dateOfFirstDeparture, $occupancyArr, Stop $stop): void
    {
        $isOccupancyDate = self::isSpitsgidsDataAvailable($dateOfFirstDeparture);
        // Check if it is in less than 2 days and MongoDB is available
        if ($isOccupancyDate && isset($occupancyArr)) {
            // Add occupancy
            $occupancyOfStationFound = false;
            $k = 0;

            while ($k < count($occupancyArr) && !$occupancyOfStationFound) {
                if ($stop->station->{'@id'} == $occupancyArr[$k]["from"]) {
                    $occupancyURI = OccupancyOperations::NumberToURI($occupancyArr[$k]["occupancy"]);
                    $stop->occupancy = new \stdClass();
                    $stop->occupancy->{'@id'} = $occupancyURI;
                    $stop->occupancy->name = basename($occupancyURI);
                    $occupancyOfStationFound = true;
                }
                $k++;
            }

            if (!isset($stop->occupancy)) {
                $unknown = OccupancyOperations::getUnknown();
                $stop->occupancy = new \stdClass();
                $stop->occupancy->{'@id'} = $unknown;
                $stop->occupancy->name = basename($unknown);
            }
        }
    }

    /**
     * @param array $stops
     */
    protected static function ensureTrainHasLeftPreviousStop(array $stops): void
    {
        for ($i = count($stops) - 1; $i > 0; $i--) {
            if ($stops[$i]->arrived == 1) {
                $stops[$i - 1]->arrived = 1;
                $stops[$i - 1]->left = 1;
            }
        }
        // The first stop can't have arrived == 1, since there is no arrival.
        $stops[0]->arrived = 0;
    }

    private function parseVehicleInfo(array $json): VehicleInfo
    {

    }

    /**
     * @param array $json
     * @return DepartureArrival[]
     */
    private function parseVehicleStops(array $json): array
    {

    }

}
