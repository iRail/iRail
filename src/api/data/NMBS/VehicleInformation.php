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
use Irail\api\data\models\Platform;
use Irail\api\data\models\Stop;
use Irail\api\data\models\Vehicle;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\occupancy\OccupancyOperations;
use stdClass;

class VehicleInformation
{
    const HAFAS_MOBILE_API_ENDPOINT = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";

    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot, $request)
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

        $lastStop = null;

        $dataroot->vehicle = new Vehicle();
        if (strpos($request->getVehicleId(), "BE.NMBS.") === 0) {
            $dataroot->vehicle->name = $request->getVehicleId();
        } else {
            $dataroot->vehicle->name = "BE.NMBS." . $request->getVehicleId();
        }
        $dataroot->vehicle->locationX = 0;
        $dataroot->vehicle->locationY = 0;
        $dataroot->vehicle->shortname = $request->getVehicleId();
        $dataroot->vehicle->{'@id'} = 'http://irail.be/vehicle/' . $request->getVehicleId();

        $dataroot->stop = self::getData($serverData, $lang, $vehicleOccupancy, $date, $request->getVehicleId(),
            $lastStop);

        if (property_exists($lastStop, "locationX")) {
            $dataroot->vehicle->locationX = $lastStop->locationX;
            $dataroot->vehicle->locationY = $lastStop->locationY;
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
     */
    private static function getServerData($id, $date, $lang)
    {
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $jid = self::getJourneyIdForVehicleId($id, $date, $lang, $request_options);
        $result = self::getVehicleDataForJourneyId($jid, $request_options);

        return $result;
    }

    /**
     * @param $serverData
     * @param $lang
     * @param $occupancyArr
     * @param $date
     * @param $vehicleId
     * @param $laststop
     * @return array
     * @throws Exception
     */
    private static function getData($serverData, $lang, $occupancyArr, $date, $vehicleId, &$laststop)
    {
        $json = json_decode($serverData, true);

        HafasCommon::throwExceptionOnInvalidResponse($json);

        $locationDefinitions = self::parseLocationDefinitions($json);
        $vehicleDefinitions = self::parseVehicleDefinitions($json);
        $remarkDefinitions = self::parseRemarkDefinitions($json);
        $alertDefinitions = self::parseAlertDefinitions($json);


        $stops = [];
        $rawVehicle = $vehicleDefinitions[$json['svcResL'][0]['res']['journey']['prodX']];
        $direction = $json['svcResL'][0]['res']['journey']['dirTxt'];

        $date = $json['svcResL'][0]['res']['journey']['date'];

        // Determine if this date is in the spitsgids range
        $now = new DateTime();
        $requestedDate = DateTime::createFromFormat('Ymd', $date);
        $daysBetweenNowAndRequest = $now->diff($requestedDate);
        $isOccupancyDate = true;
        if ($daysBetweenNowAndRequest->d > 1 && $daysBetweenNowAndRequest->invert == 0) {
            $isOccupancyDate = false;
        }

        $stopIndex = 0;
        // TODO: pick the right train here, a train which splits has multiple parts here.
        foreach ($json['svcResL'][0]['res']['journey']['stopL'] as $rawStop) {

            if (key_exists('dTimeR', $rawStop)) {
                $departureDelay = tools::calculateSecondsHHMMSS($rawStop['dTimeR'],
                    $date, $rawStop['dTimeS'], $date);
            } else {
                $departureDelay = 0;
            }
            if (key_exists('dTimeS', $rawStop)) {
                $departureTime = tools::transformTime($rawStop['dTimeS'], $date);
            } else {
                // If the train doesn't depart from here, just use the arrival time
                $departureTime = null;
            }

            if (key_exists('aTimeR', $rawStop)) {
                $arrivalDelay = tools::calculateSecondsHHMMSS($rawStop['aTimeR'],
                    $date, $rawStop['aTimeS'], $date);
            } else {
                $arrivalDelay = 0;
            }

            if (key_exists('aTimeS', $rawStop)) {
                $arrivalTime = tools::transformTime($rawStop['aTimeS'], $date);
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

            // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
            // DepartureCanceled gives information if this stop has been canceled.
            $canceled = 0;
            $departureCanceled = 0;
            $arrivalCanceled = 0;
            if (key_exists('isCncl', $rawStop)) {
                $canceled = $rawStop['isCncl'];
            }
            if ($canceled) {
                $partiallyCanceled = 1; // Completely canceled is a special case of partially canceled
            }

            $left = 0;
            if (key_exists('dProgType', $rawStop)) {
                if ($rawStop['dProgType'] == 'REPORTED') {
                    $left = 1;
                }
                if (key_exists('dCncl', $rawStop)) {
                    $departureCanceled = $rawStop['dCncl'];
                }
                if (key_exists('aCncl', $rawStop)) {
                    $arrivalCanceled = $rawStop['aCncl'];
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
            // If the train left, it also arrived
            if ($left) {
                $arrived = 1;
            }

            // Clean the data up, sometimes arrivals don't register properly
            if ($arrived && $stopIndex > 0) {
                $stops[$stopIndex - 1]->arrived = 1;
                $stops[$stopIndex - 1]->left = 1;
            }

            $station = stations::getStationFromID($locationDefinitions[$rawStop['locX']]->id, $lang);

            $stop = new Stop();
            $stop->station = $station;

            if ($departureTime != null) {
                $stop->departureDelay = $departureDelay;
                $stop->departureCanceled = $departureCanceled;
                $stop->scheduledDepartureTime = $departureTime;

                $stop->platform = new Platform();
                $stop->platform->name = $departurePlatform;
                $stop->platform->normal = $departurePlatformNormal;

                $stop->time = $departureTime;
            } else {
                $stop->departureDelay = 0;
                $stop->departureCanceled = 0;
                $stop->scheduledDepartureTime = $arrivalTime;

                $stop->platform = new Platform();
                $stop->platform->name = $arrivalPlatform;
                $stop->platform->normal = $arrivalPlatformNormal;

                $stop->time = $arrivalTime;
            }

            $stop->scheduledArrivalTime = $arrivalTime;
            $stop->arrivalDelay = $arrivalDelay;
            $stop->arrivalCanceled = $arrivalCanceled;

            // TODO: verify date here
            $stop->departureConnection = 'http://irail.be/connections/' . substr(basename($stop->station->{'@id'}),
                    2) . '/' . $requestedDate->format('Ymd') . '/' . $rawVehicle->name;


            //for backward compatibility
            $stop->delay = $departureDelay;
            $stop->canceled = $departureCanceled;
            $stop->arrived = $arrived;
            $stop->left = $left;
            // TODO: detect
            $stop->isExtraStop = 0;
            $stops[] = $stop;

            // Store the last station to get vehicle coordinates
            if ($arrived) {
                $laststop = $stop;
            }

            // TODO: verify date here
            // Check if it is in less than 2 days and MongoDB is available
            if ($isOccupancyDate && isset($occupancyArr)) {
                // Add occupancy
                $occupancyOfStationFound = false;
                $k = 0;

                while ($k < count($occupancyArr) && !$occupancyOfStationFound) {
                    if ($station->{'@id'} == $occupancyArr[$k]["from"]) {
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

            $stopIndex++;
        }

        // When the train hasn't left yet, set location to first station
        if (!is_null($laststop)) {
            $laststop = $stops[0]->station;
        }

        return $stops;
    }

    /**
     * @param $json
     * @param array $matches
     * @return array
     */
    private static function parseAlertDefinitions($json): array
    {
        $alertDefinitions = [];
        if (key_exists('himL', $json['svcResL'][0]['res']['common'])) {
            foreach ($json['svcResL'][0]['res']['common']['himL'] as $rawAlert) {
                /*
                    "hid": "23499",
                    "type": "LOC",
                    "act": true,
                    "head": "S Gravenbrakel: Wisselstoring.",
                    "lead": "Wisselstoring.",
                    "text": "Vertraagd verkeer.<br \/><br \/> Vertragingen tussen 5 en 10 minuten zijn mogelijk.<br \/><br \/> Dienst op enkel spoor tussen Tubeke en S Gravenbrakel.",
                    "icoX": 3,
                    "prio": 25,
                    "prod": 1893,
                    "pubChL": [
                      {
                          "name": "timetable",
                        "fDate": "20171016",
                        "fTime": "082000",
                        "tDate": "20171018",
                        "tTime": "235900"
                      }
                    ]
                  }*/

                $alert = new StdClass();
                $alert->header = strip_tags($rawAlert['head']);
                $alert->description = strip_tags(preg_replace("/<a href=\".*?\">.*?<\/a>/", '', $rawAlert['text']));
                $alert->lead = strip_tags($rawAlert['lead']);

                preg_match_all("/<a href=\"(.*?)\">.*?<\/a>/", urldecode($rawAlert['text']), $matches);
                if (count($matches[1]) > 1) {
                    $alert->link = urlencode($matches[1][0]);
                }

                if (key_exists('pubChL', $rawAlert)) {
                    $alert->startTime = Tools::transformTime($rawAlert['pubChL'][0]['fTime'],
                        $rawAlert['pubChL'][0]['fDate']);
                    $alert->endTime = Tools::transformTime($rawAlert['pubChL'][0]['tTime'],
                        $rawAlert['pubChL'][0]['tDate']);
                }

                $alertDefinitions[] = $alert;
            }
        }
        return $alertDefinitions;
    }

    /**
     * @param $json
     * @return array
     */
    private static function parseRemarkDefinitions($json): array
    {
        $remarkDefinitions = [];
        if (key_exists('remL', $json['svcResL'][0]['res']['common'])) {
            foreach ($json['svcResL'][0]['res']['common']['remL'] as $rawRemark) {
                /**
                 *  "type": "I",
                 * "code": "VIA",
                 * "icoX": 5,
                 * "txtN": "Opgelet: voor deze reis heb je 2 biljetten nodig.
                 *          <a href=\"http:\/\/www.belgianrail.be\/nl\/klantendienst\/faq\/biljetten.aspx?cat=reisweg\">Meer info.<\/a>"
                 */

                $remark = new StdClass();
                $remark->code = $rawRemark['code'];
                $remark->description = strip_tags(preg_replace("/<a href=\".*?\">.*?<\/a>/", '',
                    $rawRemark['txtN']));

                $matches = [];
                preg_match_all("/<a href=\"(.*?)\">.*?<\/a>/", urldecode($rawRemark['txtN']), $matches);

                if (count($matches[1]) > 0) {
                    $remark->link = urlencode($matches[1][0]);
                }

                $remarkDefinitions[] = $remark;
            }
        }
        return $remarkDefinitions;
    }

    /**
     * @param $json
     * @return array
     */
    private static function parseVehicleDefinitions($json): array
    {
        $vehicleDefinitions = [];
        if (key_exists('prodL', $json['svcResL'][0]['res']['common'])) {
            foreach ($json['svcResL'][0]['res']['common']['prodL'] as $rawTrain) {
                /*
                     {
                       "name": "IC 545",
                       "number": "545",
                       "icoX": 3,
                       "cls": 4,
                       "prodCtx": {
                         "name": "IC   545",
                         "num": "545",
                         "catOut": "IC      ",
                         "catOutS": "007",
                         "catOutL": "IC ",
                         "catIn": "007",
                         "catCode": "2",
                         "admin": "88____"
                       }
                     },
                 */

                $vehicle = new StdClass();
                $vehicle->name = str_replace(" ", '', $rawTrain['name']);
                $vehicle->num = trim($rawTrain['prodCtx']['num']);
                $vehicle->category = trim($rawTrain['prodCtx']['catOut']);
                $vehicleDefinitions[] = $vehicle;
            }
        }
        return $vehicleDefinitions;
    }

    /**
     * @param $json
     * @return array
     */
    private static function parseLocationDefinitions($json): array
    {
        $locationDefinitions = [];
        if (key_exists('remL', $json['svcResL'][0]['res']['common'])) {
            foreach ($json['svcResL'][0]['res']['common']['locL'] as $rawLocation) {
                /*
                  {
                      "lid": "A=1@O=Namur@X=4862220@Y=50468794@U=80@L=8863008@",
                      "type": "S",
                      "name": "Namur",
                      "icoX": 1,
                      "extId": "8863008",
                      "crd": {
                        "x": 4862220,
                        "y": 50468794
                      },
                      "pCls": 100,
                      "rRefL": [
                        0
                      ]
                    }
                 */

                // S stand for station, P for Point of Interest, A for address

                $location = new StdClass();
                $location->name = $rawLocation['name'];
                $location->id = '00' . $rawLocation['extId'];
                $locationDefinitions[] = $location;
            }
        }
        return $locationDefinitions;
    }

    /**
     * @param $jid
     * @param array $request_options
     * @return bool|string
     */
    private static function getVehicleDataForJourneyId($jid, array $request_options)
    {
        $postdata = '{
        "auth":{"aid":"sncb-mobi","type":"AID"},
        "client":{"id":"SNCB","name":"NMBS","os":"Android 5.0.2","type":"AND",
            "ua":"SNCB/302132 (Android_5.0.2) Dalvik/2.1.0 (Linux; U; Android 5.0.2; HTC One Build/LRX22G)","v":302132},
        "lang":"nld",
        "svcReqL":[{"cfg":{"polyEnc":"GPA"},"meth":"JourneyDetails",
        "req":{"jid":"' . $jid . '","getTrainComposition":false}}],"ver":"1.11","formatted":false}';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::HAFAS_MOBILE_API_ENDPOINT);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @param $id
     * @param $date
     * @param $lang
     * @param array $request_options
     * @return mixed
     */
    private static function getJourneyIdForVehicleId($id, $date, $lang, array $request_options)
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
                            "input":"' . substr($id, 8) . '"
                          }
                        }
                      ],
                      "ver": "1.11",
                      "formatted": false
                    }';

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

        $jidlookup = json_decode($response, true);
        $jid = $jidlookup['svcResL'][0]['res']['jnyL'][0]['jid'];
        return $jid;
    }
}