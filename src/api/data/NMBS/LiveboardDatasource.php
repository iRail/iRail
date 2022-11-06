<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */

namespace Irail\api\data\NMBS;

use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\DepartureArrival;
use Irail\api\data\models\hafas\HafasVehicle;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Station;
use Irail\api\data\models\VehicleInfo;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\occupancy\OccupancyOperations;
use Irail\api\requests\LiveboardRequest;
use stdClass;

class LiveboardDatasource
{
    /**
     * This is the entry point for the data fetching and transformation.
     *
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, LiveboardRequest $request): void
    {
        $stationr = $request->getStation();

        try {
            $dataroot->station = StationsDatasource::getStationFromName($stationr, strtolower($request->getLang()));
        } catch (Exception $e) {
            throw new Exception('Could not find station ' . $stationr, 404);
        }
        $request->setStation($dataroot->station);

        if (strtoupper(substr($request->getArrdep(), 0, 1)) == 'A') {
            self::fillDataRootWithArrivalData($dataroot, $request);
        } elseif (strtoupper(substr($request->getArrdep(), 0, 1)) == 'D') {
            self::FillDataRootWithDepartureData($dataroot, $request);
        } else {
            throw new Exception('Not a good timeSel value: try ARR or DEP', 400);
        }
    }

    /**
     * @param DataRoot $dataroot
     * @param LiveboardRequest $request
     * @throws Exception
     */
    private static function fillDataRootWithArrivalData(DataRoot $dataroot, LiveboardRequest $request): void
    {
        $nmbsCacheKey = self::getNmbsCacheKey(
            $dataroot->station,
            $request->getTime(),
            $request->getDate(),
            $request->getLang(),
            'arr'
        );
        $xml = Tools::getCachedObject($nmbsCacheKey);

        if ($xml === false) {
            $xml = self::fetchDataFromNmbs(
                $dataroot->station,
                $request->getTime(),
                $request->getDate(),
                $request->getLang(),
                'arr'
            );

            if (empty($xml)) {
                throw new Exception("No response from NMBS/SNCB", 504);
            }

            Tools::setCachedObject($nmbsCacheKey, $xml);
        } else {
            Tools::sendIrailCacheResponseHeader(true);
        }

        $dataroot->arrival = self::parseNmbsData($xml, $request->getLang());
    }

    /**
     * @param DataRoot $dataroot
     * @param LiveboardRequest $request
     * @throws Exception
     */
    private static function FillDataRootWithDepartureData(DataRoot $dataroot, LiveboardRequest $request): void
    {
        $nmbsCacheKey = self::getNmbsCacheKey(
            $dataroot->station,
            $request->getTime(),
            $request->getDate(),
            $request->getLang(),
            'dep'
        );
        $html = Tools::getCachedObject($nmbsCacheKey);

        if ($html === false) {
            $html = self::fetchDataFromNmbs(
                $dataroot->station,
                $request->getTime(),
                $request->getDate(),
                $request->getLang(),
                'dep'
            );
            Tools::setCachedObject($nmbsCacheKey, $html);
        } else {
            Tools::sendIrailCacheResponseHeader(true);
        }

        $dataroot->departure = self::parseNmbsData($html, $request->getLang());
    }

    /**
     * Get a unique key to identify data in the in-memory cache which reduces the number of requests to the NMBS.
     * @param Station $station
     * @param string $time
     * @param string $date
     * @param string $lang
     * @param string $timeSel
     * @return string
     */
    public static function getNmbsCacheKey(Station $station, string $time, string $date, string $lang, string $timeSel): string
    {
        return 'NMBSLiveboard|' . join('.', [
                $station->id,
                str_replace(':', '.', $time),
                $date,
                $timeSel,
                $lang,
            ]);
    }

    /**
     * Fetch JSON data from the NMBS.
     *
     * @param Station $station
     * @param string $time
     * @param string $date
     * @param string $lang
     * @param string $timeSel
     * @return string
     */
    private static function fetchDataFromNmbs(Station $station, string $time, string $date, string $lang, string $timeSel): string
    {
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";

        $postdata = [
            'auth' =>
                [
                    'aid' => 'sncb-mobi',
                    'type' => 'AID',
                ],
            'client' =>
                [
                    'id' => 'SNCB',
                    'name' => 'NMBS',
                    'os' => 'Android 5.0.2',
                    'type' => 'AND',
                    'ua' => 'SNCB/302132 (Android_5.0.2) Dalvik/2.1.0 (Linux; U; Android 5.0.2; HTC One Build/LRX22G)',
                    'v' => 302132,
                ],
            'lang' => $lang,
            'svcReqL' =>
                [
                    0 =>
                        [
                            'cfg' =>
                                [
                                    'polyEnc' => 'GPA',
                                ],
                            'meth' => 'StationBoard',
                            'req' =>
                                [
                                    'date' => $date,
                                    'jnyFltrL' =>
                                        [
                                            0 =>
                                                [
                                                    'mode' => 'BIT',
                                                    'type' => 'PROD',
                                                    'value' => '1010111',
                                                ],
                                        ],
                                    'stbLoc' =>
                                        [
                                            'lid' => 'A=1@O=' . $station->name . '@U=80@L=00' . $station->_hafasId . '@B=1@p=1429490515@',
                                            'name' => '' . $station->name . '',
                                            'type' => 'S',
                                        ],
                                    'time' => str_replace(':', '', $time) . '00',
                                    'getPasslist' => false,
                                    'getTrainComposition' => false,
                                    'maxJny' => 50
                                ]
                        ]
                ],
            'ver' => '1.11',
            'formatted' => false,
        ];
        if ($timeSel == 'arr') {
            $postdata['svcReqL'][0]['req']['type'] = 'ARR';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata, JSON_UNESCAPED_SLASHES));
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
     * Parse the JSON data received from the NMBS.
     *
     * @param string $serverData
     * @param string $lang
     * @return array
     * @throws Exception
     */
    private static function parseNmbsData(string $serverData, string $lang): array
    {
        if (empty($serverData)) {
            throw new Exception("The server did not return any data.", 500);
        }

        $json = json_decode($serverData, true);

        HafasCommon::throwExceptionOnLegacyInvalidResponse($json);

        // A Hafas API response contains all locations, trains, ... in separate lists to prevent duplicate data.
        // Get all those lists so we can read the actual data.
        $locationDefinitions = HafasCommon::parseLocationDefinitions($json);
        $vehicleDefinitions = HafasCommon::parseVehicleDefinitions($json);
        $remarkDefinitions = HafasCommon::parseRemarkDefinitions($json);
        $alertDefinitions = HafasCommon::parseAlertDefinitions($json);

        // Now we'll actually read the departures/arrivals information.
        $nodes = [];
        $currentStation = StationsDatasource::getStationFromID($locationDefinitions[0]->id, $lang);
        foreach ($json['svcResL'][0]['res']['jnyL'] as $stop) {
            /*
             *   "jid": "1|586|1|80|26102017",
                                    "date": "20171026",
                                    "prodX": 0,
                                    "dirTxt": "Eeklo",
                                    "status": "P",
                                    "isRchbl": true,
                                    "stbStop": {
                                        "locX": 0,
                                        "idx": 7,
                                        "dProdX": 0,
                                        "dPlatfS": "2",
                                        "dPlatfR": "1",
                                        "dTimeS": "141200",
                                        "dTimeR": "141200",
                                        "dProgType": "PROGNOSED"
                                    }
             */

            // The date of this departure
            $date = $stop['date'];

            $delay = self::parseDelayInSeconds($stop, $date);

            // parse the scheduled time of arrival/departure and the vehicle (which is returned as a number to look up in the vehicle definitions list)
            list($unixtime, $hafasVehicle) = self::parseScheduledTimeAndVehicle($stop, $date, $vehicleDefinitions);
            // parse information about which platform this train will depart from/arrive to.
            list($platform, $isPlatformNormal) = self::parsePlatformData($stop);

            // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
            // DepartureCanceled gives information if this stop has been canceled.
            list($stopCanceled, $left) = self::determineCanceledAndLeftStatus($stop);

            $isExtraTrain = 0;
            if (key_exists('status', $stop) && $stop['status'] == 'A') {
                $isExtraTrain = 1;
            }

            $station = StationsDatasource::getStationFromName($stop['dirTxt'], $lang);

            // Now all information has been parsed. Put it in a nice object.

            $stopAtStation = new DepartureArrival();
            $stopAtStation->delay = $delay;
            $stopAtStation->station = $station;
            $stopAtStation->time = $unixtime;
            $stopAtStation->vehicle = new VehicleInfo($hafasVehicle);
            $stopAtStation->platform = new Platform($platform, $isPlatformNormal);
            $stopAtStation->canceled = $stopCanceled;
            // Include partiallyCanceled, but don't include canceled.
            // PartiallyCanceled might mean the next 3 stations are canceled while this station isn't.
            // Canceled means all stations are canceled, including this one
            // TODO: enable partially canceled as soon as it's reliable, ATM it still marks trains which aren't partially canceled at all
            // $stopAtStation->partiallyCanceled = $partiallyCanceled;
            $stopAtStation->left = $left;
            $stopAtStation->isExtra = $isExtraTrain;
            $stopAtStation->departureConnection = 'http://irail.be/connections/' . substr(
                basename($currentStation->{'@id'}),
                2
            ) . '/' . date('Ymd', $unixtime) . '/' . $hafasVehicle->name;

            // Add occuppancy data, if available
            $stopAtStation = self::getDepartureArrivalWithAddedOccuppancyData($currentStation, $stopAtStation, $date);

            $nodes[] = $stopAtStation;
        }

        return array_merge($nodes); //array merge reindexes the array
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
            } elseif (key_exists('dPlatfS', $stop['stbStop'])) {
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
            } elseif (key_exists('aPlatfS', $stop['stbStop'])) {
                $platform = $stop['stbStop']['aPlatfS'];
                $isPlatformNormal = true;
            } else {
                // TODO: is this what we want when we don't know the platform?
                $platform = "?";
                $isPlatformNormal = true;
            }
        }
        return array($platform, $isPlatformNormal);
    }

    /**
     * Parse both the vehicle used and the scheduled time of departure or arrival.
     * @param $stop
     * @param $date
     * @param HafasVehicle[] $vehicleDefinitions
     * @return array
     */
    private static function parseScheduledTimeAndVehicle($stop, $date, array $vehicleDefinitions): array
    {
        // Depending on whether we're showing departures or arrivals, we should load different fields
        if (key_exists('dProdX', $stop['stbStop'])) {
            // Departures
            $unixtime = Tools::transformTime($stop['stbStop']['dTimeS'], $date);
            $vehicle = $vehicleDefinitions[$stop['stbStop']['dProdX']];
        } else {
            // Arrivals
            $unixtime = Tools::transformTime($stop['stbStop']['aTimeS'], $date);
            $vehicle = $vehicleDefinitions[$stop['stbStop']['aProdX']];
        }
        return array($unixtime, $vehicle);
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
        } elseif (key_exists('aProgType', $stop['stbStop'])) {
            if ($stop['stbStop']['aProgType'] == 'REPORTED') {
                $left = 1;
            }
            if (key_exists('aCncl', $stop['stbStop'])) {
                $stopCanceled = $stop['stbStop']['aCncl'];
            }
        }
        return array($stopCanceled, $left);
    }

    /**
     * Parse the delay based on the difference between the scheduled and real departure/arrival times.
     * @param $stop
     * @param $date
     * @return int
     */
    private static function parseDelayInSeconds($stop, $date): int
    {
        if (key_exists('dTimeR', $stop['stbStop'])) {
            $delay = Tools::calculateSecondsHHMMSS(
                $stop['stbStop']['dTimeR'],
                $date,
                $stop['stbStop']['dTimeS'],
                $date
            );
        } elseif (key_exists('aTimeR', $stop['stbStop'])) {
            $delay = Tools::calculateSecondsHHMMSS(
                $stop['stbStop']['aTimeR'],
                $date,
                $stop['stbStop']['aTimeS'],
                $date
            );
        } else {
            $delay = 0;
        }
        return $delay;
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param Station $currentStation
     * @param DepartureArrival $stopAtStation
     * @param $date
     * @return DepartureArrival
     */
    private static function getDepartureArrivalWithAddedOccuppancyData(Station $currentStation, DepartureArrival $stopAtStation, $date): DepartureArrival
    {
        if (!is_null($currentStation)) {
            try {
                $occupancy = OccupancyOperations::getOccupancyURI(
                    $stopAtStation->vehicle->{'@id'},
                    $currentStation->{'@id'},
                    $date
                );

                // Check if the MongoDB module is set up. If not, the occupancy score will not be returned.
                if (!is_null($occupancy)) {
                    $stopAtStation->occupancy = new stdClass();
                    $stopAtStation->occupancy->name = basename($occupancy);
                    $stopAtStation->occupancy->{'@id'} = $occupancy;
                }
            } catch (Exception $e) {
                // Database connection failed, in the future a warning could be given to the owner of iRail
            }
        }
        return $stopAtStation;
    }
}
