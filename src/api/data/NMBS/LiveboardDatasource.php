<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */

namespace Irail\api\data\NMBS;

use DateTime;
use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\DepartureArrival;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Station;
use Irail\api\data\models\VehicleInfo;
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
        } else {
            if (strtoupper(substr($request->getArrdep(), 0, 1)) == 'D') {
                self::FillDataRootWithDepartureData($dataroot, $request);
            } else {
                throw new Exception('Not a good timeSel value: try ARR or DEP', 400);
            }
        }
    }

    /**
     * @param DataRoot         $dataroot
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

        $dataroot->arrival = self::parseNmbsData($xml, $dataroot->station, true, $request->getLang());
    }

    /**
     * @param DataRoot         $dataroot
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

        $dataroot->departure = self::parseNmbsData($html, $dataroot->station, false, $request->getLang());
    }

    /**
     * Get a unique key to identify data in the in-memory cache which reduces the number of requests to the NMBS.
     * @param Station $station
     * @param string  $time
     * @param string  $date
     * @param string  $lang
     * @param string  $timeSel
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
     * @param string  $time Time in hh:mm format
     * @param string  $date Date in YYYYmmdd format
     * @param string  $lang
     * @param string  $timeSel
     * @return string
     */
    private static function fetchDataFromNmbs(Station $station, string $time, string $date, string $lang, string $timeSel): string
    {
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $url = "https://mobile-riv.api.belgianrail.be/api/v1.0/dacs";
        $dateTime = DateTime::createFromFormat('Ymd H:i', $date . ' ' . $time);
        $formattedDateTimeStr = $dateTime->format('Y-m-d H:i:s');

        $parameters = [
            'query'   => ($timeSel == 'arr') ? 'ArrivalsApp' : 'DeparturesApp', // include intermediate stops along the way
            'UicCode' => substr($station->_hafasId, 2),
            'FromDate' => $formattedDateTimeStr, // requires date in 'yyyy-mm-dd hh:mm:ss' format TODO: figure out how this works
            'Count'   => 100, // include intermediate stops along the way
            // language is not passed, responses contain both Dutch and French destinations
        ];
        $url = $url . '?' . http_build_query($parameters, "", null,);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl']);
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
    private static function parseNmbsData(string $serverData, Station $station, bool $isArrivalBoard, string $lang): array
    {
        if (empty($serverData)) {
            throw new Exception("The server did not return any data.", 500);
        }

        $json = json_decode($serverData, true);
        // Now we'll actually read the departures/arrivals information.
        $nodes = [];
        foreach ($json['entries'] as $stop) {
            /*
             * {
             *    "EntryDate": "2022-11-07 19:55:00",
             *    "UicCode": "8821006",
             *    "TrainNumber": 9267,
             *    "CommercialType": "IC",
             *    "PlannedDeparture": "2022-11-07 20:44:00",
             *    "DestinationNl": "BREDA (NL) Amsterdam Cs (NL)",
             *    "DestinationFr": "BREDA (NL) Amsterdam Cs (NL)",
             *    "Platform": "22",
             *    "ExpectedDeparture": "2022-11-07 21:26:00",
             *    "DepartureDelay": "00:42:00",
             *    "Destination1UicCode": "8400058"
             *  },
             */

            // The date of this departure
            $plannedDateTime = DateTime::createFromFormat('Y-m-d H:i:s',
                $isArrivalBoard ? $stop['PlannedArrival'] : $stop['PlannedDeparture']
            );
            $unixtime = $plannedDateTime->getTimestamp();

            $delay = self::parseDelayInSeconds($isArrivalBoard ? $stop['ArrivalDelay'] : $stop['DepartureDelay']);

            // parse the scheduled time of arrival/departure and the vehicle (which is returned as a number to look up in the vehicle definitions list)
            // $hafasVehicle] = self::parseScheduledTimeAndVehicle($stop, $date, $vehicleDefinitions);
            // parse information about which platform this train will depart from/arrive to.
            $platform = $stop['Platform'];
            $isPlatformNormal = 1; // TODO:  reverse-engineer and implement

            // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
            // DepartureCanceled gives information if this stop has been canceled.
            $stopCanceled = 0; // TODO: reverse-engineer and implement
            $left = 0; // TODO: probably no longer supported

            $isExtraTrain = 0; // TODO: probably no longer supported
            if (key_exists('status', $stop) && $stop['status'] == 'A') {
                $isExtraTrain = 1;
            }
            $direction = StationsDatasource::getStationFromID($isArrivalBoard ? $stop['Origin1UicCode'] : $stop['Destination1UicCode'], $lang);
            $vehicleInfo = new VehicleInfo($stop['CommercialType'], $stop['TrainNumber']);

            // Now all information has been parsed. Put it in a nice object.
            $stopAtStation = new DepartureArrival();
            $stopAtStation->delay = $delay;
            $stopAtStation->station = $direction;
            $stopAtStation->time = $unixtime;
            $stopAtStation->vehicle = $vehicleInfo;
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
                    basename($station->{'@id'}),
                    2
                ) . '/' . date('Ymd', $unixtime) . '/' . $vehicleInfo->shortname;

            // Add occuppancy data, if available
            $stopAtStation = self::getDepartureArrivalWithAddedOccuppancyData($station, $stopAtStation, $plannedDateTime->format('Ymd'));

            $nodes[] = $stopAtStation;
        }

        return array_merge($nodes); //array merge reindexes the array
    }

    /**
     * Parse the delay based on a string in hh:mm:ss format
     * @param string $delayString The delay string
     * @return int
     */
    private static function parseDelayInSeconds(?string $delayString): int
    {
        if ($delayString == null) {
            return 0;
        }
        sscanf($delayString, "%d:%d:%d", $hours, $minutes, $seconds);
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param Station          $currentStation
     * @param DepartureArrival $stopAtStation
     * @param                  $date
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
