<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 * This will fetch all vehicledata for the NMBS.
 *   * fillDataRoot will fill the entire dataroot with vehicleinformation
 */

namespace Irail\api\data\NMBS;

use DateTime;
use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\Alert;
use Irail\api\data\models\hafas\HafasVehicle;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Stop;
use Irail\api\data\models\VehicleInfo;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\occupancy\OccupancyOperations;
use Irail\api\requests\VehicleinformationRequest;

class VehicleDatasource
{
    const HAFAS_MOBILE_API_ENDPOINT = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";

    /**
     * @param DataRoot $dataroot
     * @param VehicleinformationRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, VehicleinformationRequest $request)
    {
        $lang = $request->getLang();
        $date = $request->getDate();

        $nmbsCacheKey = self::getNmbsCacheKey($request->getVehicleId(), $date, $lang);
        $serverData = Tools::getCachedObject($nmbsCacheKey);
        if ($serverData === false) {
            $serverData = self::getServerData($request->getVehicleId(), $date, $lang);
            Tools::setCachedObject($nmbsCacheKey, $serverData);
        }

        $vehicleOccupancy = OccupancyOperations::getOccupancy($request->getVehicleId(), $date);

        // Use this to check if the MongoDB module is set up. If not, the occupancy score will not be returned
        if (!is_null($vehicleOccupancy)) {
            $vehicleOccupancy = iterator_to_array($vehicleOccupancy);
        }

        $hafasVehicle = self::getVehicleDetails($serverData);
        $dataroot->vehicle = new VehicleInfo($hafasVehicle);
        $dataroot->vehicle->locationX = 0;
        $dataroot->vehicle->locationY = 0;

        $dataroot->stop = self::getStops($serverData, $lang, $vehicleOccupancy);

        $lastStop = $dataroot->stop[0];
        foreach ($dataroot->stop as $stop) {
            if ($stop->arrived) {
                $lastStop = $stop;
            }
        }

        if ($request->getAlerts() && self::getAlerts($serverData)) {
            $dataroot->alert = self::getAlerts($serverData);
        }

        if (!is_null($lastStop)) {
            $dataroot->vehicle->locationX = $lastStop->station->locationX;
            $dataroot->vehicle->locationY = $lastStop->station->locationY;
        }
    }

    public static function getNmbsCacheKey($id, $date, $lang)
    {
        return 'NMBSVehicle|' . join('.', [
                $id,
                $date,
                $lang,
            ]);
    }

    /**
     * @param $id
     * @param $lang
     * @return mixed
     * @throws Exception
     */
    private static function getServerData($id, $date, $lang)
    {
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $jid = self::getJourneyIdForVehicleId($id, $date, $lang, $request_options);
        return self::getVehicleDataForJourneyId($jid, $lang, $request_options);
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
     * @param $jid
     * @param string $lang The preferred language for alerts and messages
     * @param array $request_options request options such as referer and user agent
     * @return bool|string
     */
    private static function getVehicleDataForJourneyId($jid, string $lang, array $request_options)
    {
        $postdata = '{
        "auth":{"aid":"sncb-mobi","type":"AID"},
        "client":{"id":"SNCB","name":"NMBS","os":"Android 5.0.2","type":"AND",
            "ua":"SNCB/302132 (Android_5.0.2) Dalvik/2.1.0 (Linux; U; Android 5.0.2; HTC One Build/LRX22G)","v":302132},
        "lang":"' . $lang . '",
        "svcReqL":[{"cfg":{"polyEnc":"GPA"},"meth":"JourneyDetails",
        "req":{"jid":"' . $jid . '","getTrainComposition":false}}],"ver":"1.11","formatted":false}';
        $response = self::makeRequestToNmbs($postdata, $request_options);
        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../storage/debug-vehicle-' . $jid . '-' . time() . '.log',
                $response
            );
        }
        return $response;
    }

    /**
     * @param $requestedVehicleId
     * @param $date
     * @param $lang
     * @param array $request_options
     * @return string Journey ID
     * @throws Exception
     */
    private static function getJourneyIdForVehicleId(string $requestedVehicleId, string $date, string $lang, array $request_options): string
    {
        $postdata = '{
                      "auth": {
                        "aid": "sncb-mobi",
                        "type": "AID"
                      },
                      "client": {
                        "id": "SNCB",
                        "name": "NMBS",
                        "os": "Android 5.0.2",
                        "type": "AND",
                        "ua": "SNCB\/302132 (Android_5.0.2) Dalvik\/2.1.0 (Linux; U; Android 5.0.2; HTC One Build\/LRX22G)",
                        "v": 302132
                      },
                     "lang":"' . $lang . '",
                      "svcReqL": [
                        {
                          "cfg": {
                            "polyEnc": "GPA"
                          },
                          "meth": "JourneyMatch",
                          "req": {
                            "date": "' . $date . '",
                            "jnyFltrL": [
                              {
                                "mode": "BIT",
                                "type": "PROD",
                                "value": "11101111000111"
                              }
                            ],
                            "input":"' . $requestedVehicleId . '"
                          }
                        }
                      ],
                      "ver": "1.11",
                      "formatted": false
                    }';
        // Result contains a list of journeys, with the vehicle short name, origin and destination
        // including departure and arrival times.
        /*
         *           {
         *   "jid": "1|1|0|80|8082020",
         *   "date": "20200808",
         *   "prodX": 0,
         *   "stopL": [
         *     {
         *      "locX": 0,
         *      "dTimeS": "182900"
         *    },
         *    {
         *      "locX": 1,
         *      "aTimeS": "213500"
         *    }
         *  ],
         *  "sDaysL": [
         *    {
         *      "sDaysR": "not every day",
         *      "sDaysI": "11. Apr until 12. Dec 2020 Sa, Su; also 13. Apr, 1., 21. May, 1. Jun, 21. Jul, 11. Nov",
         *      "sDaysB": "000000000000000000000000000003860C383062C1C3060C183060D183060C183060C183060C183060C983060C10"
         *    }
         *   ]
         * },
         */
        $response = self::makeRequestToNmbs($postdata, $request_options);
        $json = json_decode($response, true);

        // Verify that the vehicle number matches with the query.
        // The best match should be on top, so we don't look further than the first response.
        try {
            HafasCommon::throwExceptionOnInvalidResponse($json);
        } catch (Exception $exception) {
            // An error in the journey id search should result in a 404, not a 500 error.
            throw new Exception("Vehicle not found", 404, $exception);
        }

        $vehicleDefinitions = HafasCommon::parseVehicleDefinitions($json);
        $vehicle = $vehicleDefinitions[$json['svcResL'][0]['res']['jnyL'][0]['prodX']];
        if (preg_match("/[A-Za-z]/", $requestedVehicleId) != false) {
            // The search string contains letters, so we try to match train type and number (IC xxx)
            if (preg_replace("/[^A-Za-z0-9]/", "", $vehicle->name) !=
                preg_replace("/[^A-Za-z0-9]/", "", $requestedVehicleId)) {
                throw new Exception("Vehicle not found", 404);
            }
        } else {
            // The search string contains no letters, so we try to match the train number (Train 538)
            if ($requestedVehicleId != $vehicle->num) {
                throw new Exception("Vehicle number not found", 404);
            }
        }

        return $json['svcResL'][0]['res']['jnyL'][0]['jid'];
    }

    /**
     * @param string $postdata
     * @param array $request_options
     * @return string|False
     */
    private static function makeRequestToNmbs(string $postdata, array $request_options)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::HAFAS_MOBILE_API_ENDPOINT);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
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
}
