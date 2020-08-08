<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 * This will return information about 1 specific route for the NMBS.
 *
 * fillDataRoot will fill the entire dataroot with connections
 */

namespace Irail\api\data\NMBS;

use DateTime;
use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\Connection;
use Irail\api\data\models\DepartureArrival;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Station;
use Irail\api\data\models\Via;
use Irail\api\data\models\ViaDepartureArrival;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\data\NMBS\tools\VehicleIdTools;
use Irail\api\occupancy\OccupancyOperations;
use Irail\api\requests\ConnectionsRequest;
use stdClass;

class Connections
{
    /**
     * @param $dataroot
     * @param $request
     */
    const TYPE_TRANSPORT_BITCODE_ALL = '10101110111';
    const TYPE_TRANSPORT_BITCODE_ONLY_TRAINS = '1010111';
    const TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS = '0010111';

    const TYPE_TRANSPORT_KEY_AUTOMATIC = 'automatic';
    const TYPE_TRANSPORT_KEY_NO_INTERNATIONAL_TRAINS = 'nointernationaltrains';
    const TYPE_TRANSPORT_KEY_TRAINS = 'trains';
    const TYPE_TRANSPORT_KEY_ALL = 'all';

    /**
     * This is the entry point for the data fetching and transformation.
     * @param                    $dataroot
     * @param ConnectionsRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, ConnectionsRequest $request): void
    {
        $from = $request->getFrom();
        if (count(explode('.', $request->getFrom())) > 1) {
            $from = Stations::getStationFromID($request->getFrom(), $request->getLang());
            $from = $from->name;
        }
        $to = $request->getTo();
        if (count(explode('.', $request->getTo())) > 1) {
            $to = Stations::getStationFromID($request->getTo(), $request->getLang());
            $to = $to->name;
        }
        $dataroot->connection = self::scrapeConnections(
            $from,
            $to,
            $request->getTime(),
            $request->getDate(),
            $request->getLang(),
            $request->getTimeSel(),
            $request->getTypeOfTransport(),
            $request
        );
    }

    /**
     * @param string $from The name of the origin station
     * @param string $to The name of the destination station
     * @param string $time The time, in hh:mm format
     * @param string $date The date, in YYYYmmdd format
     * @param string $lang The ISO2 language code, indicating in which language station names should be returned
     * @param string $timeSel Whether to filter by departure or arrival time
     * @param string $typeOfTransport The key identifying the types of transport which can be used
     * @param              $request
     * @return array
     * @throws Exception
     */
    private static function scrapeConnections(
        string $from,
        string $to,
        string $time,
        string $date,
        string $lang,
        string $timeSel,
        string $typeOfTransport,
        ConnectionsRequest $request
    ) {
        // TODO: clean the whole station name/id to object flow
        $stations = self::getStationsFromName($from, $to, $lang, $request);

        $nmbsCacheKey = self::getNmbsCacheKey(
            $stations[0]->_hafasId,
            $stations[1]->_hafasId,
            $lang,
            $time,
            $date,
            $timeSel,
            $typeOfTransport
        );

        $xml = Tools::getCachedObject($nmbsCacheKey);
        if ($xml === false) {
            $xml = self::requestHafasXml($stations[0], $stations[1], $lang, $time, $date, $timeSel, $typeOfTransport);

            if (empty($xml)) {
                throw new Exception("No response from NMBS/SNCB", 504);
            }

            Tools::setCachedObject($nmbsCacheKey, $xml);
        } else {
            Tools::sendIrailCacheResponseHeader(true);
        }

        $connections = self::parseConnectionsAPI($xml, $lang, $request);

        $requestedDate = DateTime::createFromFormat('Ymd', $date);
        $now = new DateTime();
        $daysDiff = $now->diff($requestedDate);

        if (intval($daysDiff->format('%R%a')) >= 2) {
            return $connections;
        } else {
            return self::addOccupancy($connections, $date);
        }
    }

    /**
     * This function converts 2 station names into two stations, which are returned as an array
     *
     * @param string $from
     * @param string $to
     * @param string $lang
     * @param ConnectionsRequest $request
     * @return array
     * @throws Exception
     */
    private static function getStationsFromName(string $from, string $to, string $lang, ConnectionsRequest $request)
    {
        try {
            $station1 = Stations::getStationFromName($from, $lang);
            $station2 = Stations::getStationFromName($to, $lang);

            if (isset($request)) {
                $request->setFrom($station1);
                $request->setTo($station2);
            }
            return [$station1, $station2];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 404);
        }
    }

    /**
     * Get a key to identify this request. Requests which will result in a different response will receive a different key
     * @param $idfrom
     * @param $idto
     * @param $lang
     * @param $time
     * @param $date
     * @param $timeSel
     * @param $typeOfTransport
     * @return string
     */
    public static function getNmbsCacheKey(
        string $idfrom,
        string $idto,
        string $lang,
        string $time,
        string $date,
        string $timeSel,
        string $typeOfTransport
    ) {
        return 'NMBSConnections|' . join('.', [
                $idfrom,
                $idto,
                $lang,
                str_replace(':', '.', $time),
                $date,
                $timeSel,
                $typeOfTransport,
            ]);
    }

    /**
     * @param Station $stationFrom
     * @param Station $stationTo
     * @param string $lang
     * @param string $time
     * @param string $date
     * @param string $timeSel
     * @param string $typeOfTransport
     * @return string
     */
    private static function requestHafasXml(
        Station $stationFrom,
        Station $stationTo,
        string $lang,
        string $time,
        string $date,
        string $timeSel,
        string $typeOfTransport
    ): string {
        $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";

        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $typeOfTransportCode = self::getTypeOfTransportBitcode($stationFrom, $stationTo, $typeOfTransport);


        if (strpos($timeSel, 'dep') === 0) {
            $timeSel = 0;
        } else {
            $timeSel = 1;
        }

        $postdata = self::createNmbsPayload(
            $stationFrom,
            $stationTo,
            $lang,
            $time,
            $date,
            $timeSel,
            $typeOfTransportCode
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);

        $response = curl_exec($ch);

        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../storage/debug-connections-' . $stationFrom->_hafasId . '-' . $stationTo->_hafasId . '-' . time() . '.log',
                $response
            );
        }

        curl_close($ch);
        return $response;
    }

    /**
     * Get a string of bits indicating which types of transport are allowed.
     * @param Station $stationFrom The station from where the traveller will start.
     * @param Station $stationTo The station where the traveller will travel to.
     * @param string $typeOfTransportKey The allowed types of transport. Should be one of the TYPE_TRANSPORT_KEY_* constants.
     * @return string
     */
    private static function getTypeOfTransportBitcode(Station $stationFrom, Station $stationTo, string $typeOfTransportKey): string
    {
        // Convert the type of transport key to a bitcode needed in the request payload
        // Automatic is the default type, which prevents that local trains aren't shown because a high-speed train provides a faster connection
        if ($typeOfTransportKey == self::TYPE_TRANSPORT_KEY_AUTOMATIC) {
            // 2 national stations: no international trains
            // Internation station: all
            if (strpos($stationFrom->_hafasId, '0088') === 0 && strpos($stationTo->_hafasId, '0088') === 0) {
                $typeOfTransportCode = self::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;
            } else {
                $typeOfTransportCode = self::TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
            }
        } elseif ($typeOfTransportKey == self::TYPE_TRANSPORT_KEY_NO_INTERNATIONAL_TRAINS) {
            $typeOfTransportCode = self::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;
        } elseif ($typeOfTransportKey == self::TYPE_TRANSPORT_KEY_TRAINS) {
            $typeOfTransportCode = self::TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
        } elseif ($typeOfTransportKey == self::TYPE_TRANSPORT_KEY_ALL) {
            $typeOfTransportCode = self::TYPE_TRANSPORT_BITCODE_ALL;
        } else {
            // All trains is the default
            $typeOfTransportCode = self::TYPE_TRANSPORT_BITCODE_ONLY_TRAINS;
        }
        return $typeOfTransportCode;
    }

    /**
     * @param Station $stationFrom
     * @param Station $stationTo
     * @param string $lang
     * @param string $time
     * @param string $date
     * @param string $timeSel
     * @param string $typeOfTransportBitCode A string of 1s and 0s, indicating which types of transport are allowed.
     * @return string The HTTP POST payload for the request to the NMBS.
     */
    public static function createNmbsPayload(
        Station $stationFrom,
        Station $stationTo,
        string $lang,
        string $time,
        string $date,
        string $timeSel,
        string $typeOfTransportBitCode
    ): string {
        // numF: number of results: server-side capped to 5, but ask 10 in case they'd let us
        $postdata = [
            'auth' => [
                'aid' => 'sncb-mobi',
                'type' => 'AID'
            ],
            'client' => [
                'id' => 'SNCB',
                'name' => 'NMBS',
                'os' => 'Android 8.0.0',
                'type' => 'AND',
                'ua' => '',
                'v' => 1000320
            ],
            // Response language (for station names)
            'lang' => $lang,
            'svcReqL' => [
                [
                    'cfg' => [
                        'polyEnc' => 'GPA'
                    ],
                    // Route query
                    'meth' => 'TripSearch',
                    'req' => [

                        // TODO: include as many parameters as possible in locations to prevent future issues
                        // Official Location ID (lid): "A=1@O=Zaventem@X=4469886@Y=50885723@U=80@L=008811221@B=1@p=1518483428@n=ac.1=GA@"
                        // "eteId": "A=1@O=Zaventem@X=4469886@Y=50885723@U=80@L=008811221@B=1@p=1518483428@n=ac.1=GA@Zaventem",
                        // "extId": "8811221",

                        // Departure station
                        'depLocL' => [
                            [
                                'lid' => 'L=' . $stationFrom->_hafasId . '@A=1@B=1@U=80@p=1578357403@n=ac.1=GA@'
                            ]
                        ],

                        // Arrival station
                        'arrLocL' => [
                            [
                                'lid' => 'L=' . $stationTo->_hafasId . '@A=1@B=1@U=80@p=1578357403@n=ac.1=GI@'
                            ]
                        ],

                        // Transport type filters
                        'jnyFltrL' => [['mode' => 'BIT', 'type' => 'PROD', 'value' => $typeOfTransportBitCode]],
                        // Search date
                        'outDate' => $date,
                        // Search time
                        'outTime' => str_replace(':', '', $time) . '00',

                        'economic' => false,
                        'extChgTime' => -1,
                        'getIST' => false,
                        // Intermediate stops
                        'getPasslist' => true,
                        // Coordinates of a line visualizing the trip (direct lines between stations, doesn't show the tracks)
                        'getPolyline' => false,
                        // Number of results
                        'numF' => 10,
                        'liveSearch' => false
                    ]
                ]
            ],
            'ver' => '1.21',
            // Don't pretty print json replies from NMBS (costs time and bandwidth)
            'formatted' => false
        ];

        // search by arrival time instead of by departure time
        if ($timeSel == 1) {
            $postdata['svcReqL'][0]['req']['outFrwd'] = false;
        }

        $postdata = json_encode($postdata);
        return $postdata;
    }

    /**
     * @param string $serverData
     * @param string $lang
     * @param          $request
     * @return Connection[]
     * @throws Exception
     */
    public static function parseConnectionsAPI(string $serverData, string $lang, ConnectionsRequest $request): array
    {
        $json = json_decode($serverData, true);

        HafasCommon::throwExceptionOnInvalidResponse($json);
        $locationDefinitions = HafasCommon::parseLocationDefinitions($json);
        $vehicleDefinitions = HafasCommon::parseVehicleDefinitions($json);
        $remarkDefinitions = HafasCommon::parseRemarkDefinitions($json);
        $alertDefinitions = HafasCommon::parseAlertDefinitions($json);

        $connections = [];
        foreach ($json['svcResL'][0]['res']['outConL'] as $conn) {
            $connections[] = self::parseHafasConnection(
                $request,
                $conn,
                $locationDefinitions,
                $vehicleDefinitions,
                $alertDefinitions,
                $remarkDefinitions,
                $lang
            );
        }

        return $connections;
    }

    /**
     * @param $request
     * @param $hafasConnection
     * @param $locationDefinitions
     * @param $vehicleDefinitions
     * @param $alertDefinitions
     * @param $remarkDefinitions
     * @param $lang
     * @return Connection
     * @throws Exception
     */
    private static function parseHafasConnection(
        ConnectionsRequest $request,
        $hafasConnection,
        $locationDefinitions,
        $vehicleDefinitions,
        $alertDefinitions,
        $remarkDefinitions,
        $lang
    ): Connection {
        $connection = new Connection();
        $connection->duration = Tools::transformDurationHHMMSS($hafasConnection['dur']);

        $connection->departure = new DepartureArrival();

        $departureStation = Stations::getStationFromID($locationDefinitions[0]->id, $lang);
        $connection->departure->station = $departureStation;

        // When a train has been cancelled mid-run, the arrival station can be different than the planned one!
        // Therefore, always parse it from the planner results
        $arrivalStation = Stations::getStationFromID($locationDefinitions[$hafasConnection['arr']['locX']]->id, $lang);


        if (key_exists('dTimeR', $hafasConnection['dep'])) {
            $connection->departure->delay = Tools::calculateSecondsHHMMSS(
                $hafasConnection['dep']['dTimeR'],
                $hafasConnection['date'],
                $hafasConnection['dep']['dTimeS'],
                $hafasConnection['date']
            );
        } else {
            $connection->departure->delay = 0;
        }
        $connection->departure->time = Tools::transformTime(
            $hafasConnection['dep']['dTimeS'],
            $hafasConnection['date']
        );

        $connection->departure->platform = self::parseDeparturePlatform($hafasConnection['dep']);

        $connection->arrival = new DepartureArrival();
        $connection->arrival->station = $arrivalStation;

        if (key_exists('aTimeR', $hafasConnection['arr'])) {
            $connection->arrival->delay = Tools::calculateSecondsHHMMSS(
                $hafasConnection['arr']['aTimeR'],
                $hafasConnection['date'],
                $hafasConnection['arr']['aTimeS'],
                $hafasConnection['date']
            );
        } else {
            $connection->arrival->delay = 0;
        }

        $connection->arrival->time = Tools::transformTime($hafasConnection['arr']['aTimeS'], $hafasConnection['date']);

        $connection->arrival->platform = self::parseArrivalPlatform($arrival = $hafasConnection['arr']);

        $trainsInConnection = self::parseHafasTrainsForConnection(
            $hafasConnection,
            $locationDefinitions,
            $vehicleDefinitions,
            $alertDefinitions,
            $lang
        );

        $connection->departure->canceled = $trainsInConnection[0]->departure->canceled;
        $connection->arrival->canceled = end($trainsInConnection)->arrival->canceled;


        $viaCount = count($trainsInConnection) - 1;

        $vias = [];
        //check if there were vias at all. Ignore the first
        if ($viaCount != 0) {
            for ($viaIndex = 0; $viaIndex < $viaCount; $viaIndex++) {
                // Update the via array
                $vias = self::constructVia($vias, $viaIndex, $trainsInConnection);
            }
        }
        $connection->via = $vias;

        // All the train alerts should go together in the connection alerts
        $connectionAlerts = [];
        foreach ($trainsInConnection as $train) {
            if (property_exists($train, 'alerts')) {
                $connectionAlerts = array_merge($connectionAlerts, $train->alerts);
            }
        }
        $connectionAlerts = array_unique($connectionAlerts, SORT_REGULAR);

        $connectionRemarks = [];
        if (key_exists('ovwMsgL', $hafasConnection)) {
            foreach ($hafasConnection['ovwMsgL'] as $message) {
                $connectionRemarks[] = $remarkDefinitions[$message['remX']];
            }
        }
        if (key_exists('footerMsgL', $hafasConnection)) {
            foreach ($hafasConnection['footerMsgL'] as $message) {
                $connectionRemarks[] = $remarkDefinitions[$message['remX']];
            }
        }

        if (count($connectionAlerts) > 0) {
            $connection->alert = $connectionAlerts;
        }

        if (count($connectionRemarks) > 0) {
            $connection->remark = $connectionRemarks;
        }

        $connection->departure->vehicle = $trainsInConnection[0]->vehicle;

        $connection->departure->stop = $trainsInConnection[0]->stops;
        array_shift($connection->departure->stop);
        array_pop($connection->departure->stop);


        $connection->departure->departureConnection = 'http://irail.be/connections/' .
            substr(basename($departureStation->{'@id'}), 2) . '/' .
            date('Ymd', $connection->departure->time) . '/' .
            substr(
                $trainsInConnection[0]->vehicle,
                strrpos($trainsInConnection[0]->vehicle, '.') + 1
            );

        $connection->departure->direction = $trainsInConnection[0]->direction;
        $connection->departure->left = $trainsInConnection[0]->left;

        $connection->departure->walking = 0;
        if (property_exists($trainsInConnection[0], 'alerts') && count($trainsInConnection[0]->alerts) > 0) {
            $connection->departure->alert = $trainsInConnection[0]->alerts;
        }

        $connection->arrival->vehicle = $trainsInConnection[count($trainsInConnection) - 1]->vehicle;
        $connection->arrival->direction = $trainsInConnection[count($trainsInConnection) - 1]->direction;
        $connection->arrival->arrived = end($trainsInConnection)->arrived;
        $connection->arrival->walking = 0;

        // No alerts for arrival objects
        /*if (property_exists(end($trains), 'alerts') && count(end($trains)->alerts) > 0) {
            $connection->arrival->alert = end($trains)->alerts;
        }*/

        self::storeIrailLogData($request, $connection, $vias);

        return $connection;
    }

    /**
     * Parse the arrival platform, and whether or not this is a normal platform or a changed one
     * @param array $departureData
     * @return Platform The platform for this departure.
     */
    private static function parseDeparturePlatform(array $departureData): Platform
    {
        return self::parsePlatform($departureData, 'dPlatfS', 'dPlatfR');
    }

    /**
     * Parse the arrival platform, and whether or not this is a normal platform or a changed one
     * @param array $arrivalData
     * @return Platform The platform for this arrival.
     */
    private static function parseArrivalPlatform(array $arrivalData): Platform
    {
        return self::parsePlatform($arrivalData, 'aPlatfS', 'aPlatfR');
    }

    /**
     * @param array $data The data object containing the platform information, for example a departure or arrival.
     * @param string $scheduledFieldName The name of the field containing information about the scheduled platform.
     * @param string $realTimeFieldName The name of the field containing information about the realtime platform.
     * @return Platform The platform for this departure/arrival.
     */
    private static function parsePlatform(array $data, string $scheduledFieldName, string $realTimeFieldName): Platform
    {
        $result = new Platform();

        if (key_exists($realTimeFieldName, $data)) {
            // Realtime correction exists
            $result->name = $data[$realTimeFieldName];
            $result->normal = false;
        } elseif (key_exists($scheduledFieldName, $data)) {
            // Only scheduled data exists
            $result->name = $data[$scheduledFieldName];
            $result->normal = true;
        } else {
            // No data
            $result->name = "?";
            $result->normal = true;
        }
        return $result;
    }


    /**
     * Parse all train objects in a connection (route/trip).
     * @param array $hafasConnection The connection object for which trains should be parsed.
     * @param array $locationDefinitions The location definitions, defined in the API response.
     * @param array $vehicleDefinitions The vehicle definitions, defined in the API response.
     * @param array $alertDefinitions The alert definitions, defined in the API response.
     * @param string $lang The language for station names etc.
     * @return array All trains in this connection.
     * @throws Exception
     */
    private static function parseHafasTrainsForConnection(
        array $hafasConnection,
        array $locationDefinitions,
        array $vehicleDefinitions,
        array $alertDefinitions,
        string $lang
    ): array {
        $trainsInConnection = [];

        // For the sake of readability: the response contains trains, not vias. Therefore, just parse the trains, and create via's based on the trains later.
        // This is way more readable compared to instantly creating the vias
        // Loop over all train rides in the list. This will also include the first train ride.
        foreach ($hafasConnection['secL'] as $trainRide) {
            if ($trainRide['dep']['locX'] == $trainRide['arr']['locX']) {
                // Don't parse a train ride from station X to that same station X.
                // NMBS/SNCB likes to include this utterly useless information to clutter their UI.
                continue;
            }

            $trainsInConnection[] = self::parseHafasTrain(
                $trainRide,
                $hafasConnection,
                $locationDefinitions,
                $vehicleDefinitions,
                $alertDefinitions,
                $lang
            );
        }
        return $trainsInConnection;
    }

    /**
     * Parse a single train object in a connection (route/trip).
     * @param array $trainRide The specific trainride to parse.
     * @param array $hafasConnection The connection object for which trains should be parsed.
     * @param array $locationDefinitions The location definitions, defined in the API response.
     * @param array $vehicleDefinitions The vehicle definitions, defined in the API response.
     * @param array $alertDefinitions The alert definitions, defined in the API response.
     * @param string $lang The language for station names etc.
     * @return StdClass The parsed train
     * @throws Exception
     */
    private static function parseHafasTrain(
        array $trainRide,
        array $hafasConnection,
        array $locationDefinitions,
        array $vehicleDefinitions,
        array $alertDefinitions,
        string $lang
    ): StdClass {
        $departPlatform = self::parseDeparturePlatform($trainRide['dep']);

        if (key_exists('dTimeR', $trainRide['dep'])) {
            $departDelay = Tools::calculateSecondsHHMMSS(
                $trainRide['dep']['dTimeR'],
                $hafasConnection['date'],
                $trainRide['dep']['dTimeS'],
                $hafasConnection['date']
            );
        } else {
            $departDelay = 0;
        }

        if ($departDelay < 0) {
            $departDelay = 0;
        }

        $arrivalTime = Tools::transformTime(
            $trainRide['arr']['aTimeS'],
            $hafasConnection['date']
        );

        $arrivalPlatform = self::parseArrivalPlatform($trainRide['arr']);


        if (key_exists('aTimeR', $trainRide['arr'])) {
            $arrivalDelay = Tools::calculateSecondsHHMMSS(
                $trainRide['arr']['aTimeR'],
                $hafasConnection['date'],
                $trainRide['arr']['aTimeS'],
                $hafasConnection['date']
            );
        } else {
            $arrivalDelay = 0;
        }

        if ($arrivalDelay < 0) {
            $arrivalDelay = 0;
        }

        $arrivalIsExtraStop = 0;
        if (key_exists('isAdd', $trainRide['arr'])) {
            $arrivalIsExtraStop = $trainRide['arr']['isAdd'];
        }

        $departureIsExtraStop = 0;
        if (key_exists('isAdd', $trainRide['dep'])) {
            $departureIsExtraStop = $trainRide['dep']['isAdd'];
        }

        $departurecanceled = false;
        $arrivalcanceled = false;

        if (key_exists('dCncl', $trainRide['dep'])) {
            $departurecanceled = $trainRide['dep']['dCncl'];
        }

        if (key_exists('aCncl', $trainRide['arr'])) {
            $arrivalcanceled = $trainRide['arr']['aCncl'];
        }

        $parsedTrain = new StdClass();
        $parsedTrain->arrival = new ViaDepartureArrival();
        $parsedTrain->arrival->time = Tools::transformTime($trainRide['arr']['aTimeS'], $hafasConnection['date']);
        $parsedTrain->arrival->delay = $arrivalDelay;
        $parsedTrain->arrival->platform = $arrivalPlatform;
        $parsedTrain->arrival->canceled = $arrivalcanceled;
        $parsedTrain->arrival->isExtraStop = $arrivalIsExtraStop;
        $parsedTrain->departure = new ViaDepartureArrival();
        $parsedTrain->departure->time = Tools::transformTime($trainRide['dep']['dTimeS'], $hafasConnection['date']);
        $parsedTrain->departure->delay = $departDelay;
        $parsedTrain->departure->platform = $departPlatform;
        $parsedTrain->departure->canceled = $departurecanceled;
        $parsedTrain->departure->isExtraStop = $departureIsExtraStop;

        $departTime = Tools::transformTime($trainRide['dep']['dTimeS'], $hafasConnection['date']);

        $parsedTrain->duration = Tools::calculateSecondsHHMMSS(
            $arrivalTime,
            $hafasConnection['date'],
            $departTime,
            $hafasConnection['date']
        );

        if (self::hasConnectionTrainDepartureBeenReported($trainRide)) {
            $parsedTrain->left = 1;
        } else {
            $parsedTrain->left = 0;
        }

        if (self::hasConnectionTrainArrivalBeenReported($trainRide)) {
            $parsedTrain->arrived = 1;
            // A train can only arrive if it left first in the previous station
            $parsedTrain->left = 1;
        } else {
            $parsedTrain->arrived = 0;
        }

        $parsedTrain->departure->station = Stations::getStationFromID(
            $locationDefinitions[$trainRide['dep']['locX']]->id,
            $lang
        );
        $parsedTrain->arrival->station = Stations::getStationFromID(
            $locationDefinitions[$trainRide['arr']['locX']]->id,
            $lang
        );

        $parsedTrain->isPartiallyCancelled = false;
        $parsedTrain->stops = [];
        if (key_exists('jny', $trainRide)) {
            if (key_exists('isPartCncl', $trainRide['jny'])) {
                $parsedTrain->isPartiallyCancelled = $trainRide['jny']['isPartCncl'];
            }

            foreach ($trainRide['jny']['stopL'] as $rawIntermediateStop) {
                $intermediateStop = self::parseHafasIntermediateStop(
                    $lang,
                    $locationDefinitions,
                    $vehicleDefinitions,
                    $rawIntermediateStop,
                    $hafasConnection
                );
                $parsedTrain->stops[] = $intermediateStop;
                if ($intermediateStop->left == 1 || $intermediateStop->arrived == 1) {
                    $parsedTrain->left = 1; // If the train has left from an intermediate stop, it has automatically left from its first stop!
                }
            }

            // Sanity check: ensure that the arrived/left status for intermediate stops is correct.
            // If a train has reached the next intermediate stop, it must have passed the previous one.
            for ($i = count($parsedTrain->stops) - 2; $i >= 0; $i--) {
                if ($parsedTrain->stops[$i + 1]->arrived) {
                    $parsedTrain->stops[$i]->left = 1;
                    $parsedTrain->stops[$i]->arrived = 1;
                }
            }

            $parsedTrain->alerts = [];
            try {
                if (key_exists('himL', $trainRide['jny']) && is_array($trainRide['jny']['himL'])) {
                    foreach ($trainRide['jny']['himL'] as $himX) {
                        $parsedTrain->alerts[] = $alertDefinitions[$himX['himX']];
                    }
                }
            } catch (Exception $ignored) {
                // ignored
            }
        }

        if ($trainRide['type'] == 'WALK') {
            // If the type is walking, there is no direction. Resolve this by hardcoding this variable.
            $parsedTrain->direction = new StdClass();
            $parsedTrain->direction->name = "WALK";
            $parsedTrain->vehicle = new StdClass();
            $parsedTrain->vehicle->name = 'WALK';
            $parsedTrain->walking = 1;
        } else {
            $parsedTrain->walking = 0;
            $parsedTrain->direction = new StdClass();
            if (key_exists('dirTxt', $trainRide['jny'])) {
                // Get the direction from the API
                $parsedTrain->direction->name = $trainRide['jny']['dirTxt'];
            } else {
                // If we can't load the direction from the data (direction is missing),
                // fill in the gap by using the furthest stop we know on this trains route.
                // This typically is the stop where the user leaves this train
                $parsedTrain->direction->name = end($parsedTrain->stops)->station->name;
            }
            $vehicleShortName = $vehicleDefinitions[$trainRide['jny']['prodX']]->name;
            $parsedTrain->vehicle = new StdClass();
            $parsedTrain->vehicle->name = 'BE.NMBS.' . $vehicleShortName;
            $parsedTrain->vehicle->{'@id'} = 'http://irail.be/vehicle/' .$vehicleShortName;
            $parsedTrain->vehicle->type = VehicleIdTools::extractTrainType($vehicleShortName);
            $parsedTrain->vehicle->number = VehicleIdTools::extractTrainNumber($vehicleShortName);
        }
        return $parsedTrain;
    }

    /**
     * Parse an intermediate stop for a train on a connection. For example, if a traveller travels from
     * Brussels South to Brussels north, Brussels central would be an intermediate stop (the train stops but
     * the traveller stays on)
     * @param $lang
     * @param $locationDefinitions
     * @param $vehicleDefinitions
     * @param $rawIntermediateStop
     * @param $conn
     * @return StdClass The parsed intermediate stop.
     * @throws Exception
     */
    private static function parseHafasIntermediateStop($lang, $locationDefinitions, $vehicleDefinitions, $rawIntermediateStop, $conn)
    {
        /* "locX": 2,
           "idx": 19,
           "aProdX": 1,
           "aTimeS": "162900",
           "aTimeR": "162900",
           "aProgType": "PROGNOSED",
           "dProdX": 1,
           "dTimeS": "163000",
           "dTimeR": "163000",
           "dProgType": "PROGNOSED",
           "isImp": true
        */
        $intermediateStop = new StdClass();
        $intermediateStop->station = Stations::getStationFromID(
            $locationDefinitions[$rawIntermediateStop['locX']]->id,
            $lang
        );

        if (key_exists('aTimeS', $rawIntermediateStop)) {
            $intermediateStop->scheduledArrivalTime = Tools::transformTime(
                $rawIntermediateStop['aTimeS'],
                $conn['date']
            );
        } else {
            $intermediateStop->scheduledArrivalTime = null;
        }

        if (key_exists('aProgType', $rawIntermediateStop)) {
            $intermediateStop->arrivalCanceled = HafasCommon::isArrivalCanceledBasedOnState($rawIntermediateStop['aProgType']);

            if ($rawIntermediateStop['aProgType'] == "REPORTED") {
                $intermediateStop->arrived = 1;
            } else {
                $intermediateStop->arrived = 0;
            }
        } else {
            $intermediateStop->arrivalCanceled = false;
            $intermediateStop->arrived = 0;
        }

        if (key_exists('aTimeR', $rawIntermediateStop)) {
            $intermediateStop->arrivalDelay = Tools::calculateSecondsHHMMSS(
                $rawIntermediateStop['aTimeR'],
                $conn['date'],
                $rawIntermediateStop['aTimeS'],
                $conn['date']
            );
        } else {
            $intermediateStop->arrivalDelay = 0;
        }


        if (key_exists('dTimeS', $rawIntermediateStop)) {
            $intermediateStop->scheduledDepartureTime = Tools::transformTime(
                $rawIntermediateStop['dTimeS'],
                $conn['date']
            );
        } else {
            $intermediateStop->scheduledDepartureTime = null;
        }

        if (key_exists('dTimeR', $rawIntermediateStop)) {
            $intermediateStop->departureDelay = Tools::calculateSecondsHHMMSS(
                $rawIntermediateStop['dTimeR'],
                $conn['date'],
                $rawIntermediateStop['dTimeS'],
                $conn['date']
            );
        } else {
            $intermediateStop->departureDelay = 0;
        }

        if (key_exists('dProgType', $rawIntermediateStop)) {
            $intermediateStop->departureCanceled = HafasCommon::isDepartureCanceledBasedOnState($rawIntermediateStop['dProgType']);

            if ($rawIntermediateStop['dProgType'] == "REPORTED") {
                $intermediateStop->left = 1;
                // A train can only leave a stop if he arrived first
                $intermediateStop->arrived = 1;
            } else {
                $intermediateStop->left = 0;
            }
        } else {
            $intermediateStop->departureCanceled = false;
            $intermediateStop->left = 0;
        }

        // Prevent null values in edge cases. If one of both values is unknown, copy the non-null value. In case both
        // are null, hope for the best
        if (!property_exists(
            $intermediateStop,
            'scheduledDepartureTime'
        ) || $intermediateStop->scheduledDepartureTime == null) {
            $intermediateStop->scheduledDepartureTime = $intermediateStop->scheduledArrivalTime;
        }
        if (!property_exists(
            $intermediateStop,
            'scheduledArrivalTime'
        ) || $intermediateStop->scheduledArrivalTime == null) {
            $intermediateStop->scheduledArrivalTime = $intermediateStop->scheduledDepartureTime;
        }

        // Some boolean about scheduled departure? First seen on an added stop
        // dInS, dInR, aOutS, aOutR are not processed at this moment
        if (key_exists('dCncl', $rawIntermediateStop)) {
            $intermediateStop->departureCanceled = $rawIntermediateStop['dCncl'];
        }

        if (key_exists('aCncl', $rawIntermediateStop)) {
            $intermediateStop->arrivalCanceled = $rawIntermediateStop['aCncl'];
        }

        if (key_exists('isAdd', $rawIntermediateStop)) {
            $intermediateStop->isExtraStop = 1;
        } else {
            $intermediateStop->isExtraStop = 0;
        }

        // The last stop does not have a departure, and therefore we cannot construct a departure URI.
        if (key_exists('dProdX', $rawIntermediateStop)) {
            $intermediateStop->departureConnection = 'http://irail.be/connections/' .
                substr($locationDefinitions[$rawIntermediateStop['locX']]->id, 2) . '/' .
                date('Ymd', $intermediateStop->scheduledDepartureTime) . '/' .
                $vehicleDefinitions[$rawIntermediateStop['dProdX']]->name;
        }
        return $intermediateStop;
    }


    /**
     * @param $vias
     * @param $viaIndex
     * @param $trains
     * @return Via[]
     */
    private static function constructVia($vias, $viaIndex, $trains): array
    {
        // A via lies between two trains. This mean that for n trains, there are n-1 vias, with n >=1
        // The n-th via lies between train n and train n+1

        $constructedVia = new Via();
        $constructedVia->arrival = new ViaDepartureArrival();
        $constructedVia->arrival->time = $trains[$viaIndex]->arrival->time;
        $constructedVia->arrival->delay = $trains[$viaIndex]->arrival->delay;
        $constructedVia->arrival->platform = $trains[$viaIndex]->arrival->platform;
        $constructedVia->arrival->canceled = $trains[$viaIndex]->arrival->canceled;
        $constructedVia->arrival->isExtraStop = $trains[$viaIndex]->arrival->isExtraStop;

        // No alerts for arrival objects
        /*if (property_exists($trains[$viaIndex], 'alerts') && count($trains[$viaIndex]->alerts) > 0) {
            $constructedVia->arrival->alert = $trains[$viaIndex]->alerts;
        }*/

        $constructedVia->arrival->arrived = $trains[$viaIndex]->arrived;

        $constructedVia->departure = new ViaDepartureArrival();
        $constructedVia->departure->time = $trains[$viaIndex + 1]->departure->time;
        $constructedVia->departure->delay = $trains[$viaIndex + 1]->departure->delay;
        $constructedVia->departure->platform = $trains[$viaIndex + 1]->departure->platform;
        $constructedVia->departure->canceled = $trains[$viaIndex + 1]->departure->canceled;
        $constructedVia->departure->isExtraStop = $trains[$viaIndex + 1]->departure->isExtraStop;
        if (property_exists(
            $trains[$viaIndex + 1],
            'alerts'
        ) && count($trains[$viaIndex + 1]->alerts) > 0) {
            $constructedVia->departure->alert = $trains[$viaIndex + 1]->alerts;
        }

        $constructedVia->departure->left = $trains[$viaIndex + 1]->left;

        $constructedVia->timeBetween = $constructedVia->departure->time - $trains[$viaIndex]->arrival->time;
        $constructedVia->direction = $trains[$viaIndex]->direction;
        $constructedVia->arrival->walking = $trains[$viaIndex]->walking;

        $constructedVia->arrival->direction = $trains[$viaIndex]->direction;

        $constructedVia->departure->walking = $trains[$viaIndex + 1]->walking;
        $constructedVia->departure->direction = $trains[$viaIndex + 1]->direction;

        $constructedVia->vehicle = $trains[$viaIndex]->vehicle;
        $constructedVia->arrival->vehicle = $trains[$viaIndex]->vehicle;
        $constructedVia->departure->vehicle = $trains[$viaIndex + 1]->vehicle;

        $constructedVia->departure->stop = $trains[$viaIndex + 1]->stops;
        array_shift($constructedVia->departure->stop); // remove departure stop
        array_pop($constructedVia->departure->stop); // remove arrival stop

        $constructedVia->station = $trains[$viaIndex]->arrival->station;

        $constructedVia->departure->departureConnection = Tools::createDepartureUri(
            $constructedVia->station,
            $constructedVia->departure->time,
            $constructedVia->departure->vehicle
        );
        $constructedVia->arrival->departureConnection = Tools::createDepartureUri(
            $constructedVia->station,
            $constructedVia->arrival->time,
            $constructedVia->arrival->vehicle
        );

        $vias[$viaIndex] = $constructedVia;
        return $vias;
    }

    /**
     * @param ConnectionsRequest $request
     * @param                    $connection
     * @param                    $vias
     */
    private static function storeIrailLogData($request, $connection, $vias): void
    {
        //Add journey options to the logs of iRail
        $journeyoptions = ["journeys" => []];
        $departureStop = $connection->departure->station;
        for ($viaIndex = 0; $viaIndex < count($vias); $viaIndex++) {
            $arrivalStop = $vias[$viaIndex]->station;
            $journeyoptions["journeys"][] = [
                "trip" => substr($vias[$viaIndex]->vehicle->name, 8),
                "departureStop" => $departureStop->{'@id'},
                "arrivalStop" => $arrivalStop->{'@id'}
            ];
            //set the next departureStop
            $departureStop = $vias[$viaIndex]->station;
        }
        //add last journey
        $journeyoptions["journeys"][] = [
            "trip" => substr($connection->arrival->vehicle->name, 8),
            "departureStop" => $departureStop->{'@id'},
            "arrivalStop" => $connection->arrival->station->{'@id'}
        ];

        $existing = $request->getJourneyOptions();
        $existing[] = $journeyoptions;
        $request->setJourneyOptions($existing);
    }

    /**
     * Add spitsgids occupancy data to the response
     *
     * @param $connections
     * @param $date
     * @return array
     */
    private static function addOccupancy($connections, $date): array
    {
        $occupancyConnections = $connections;

        // Use this to track if the MongoDB module is set up. If not, it will be detected in the first iteration and
        // the occupancy score will not be returned.
        $mongodbExists = true;
        $i = 0;

        try {
            while ($i < count($occupancyConnections) && $mongodbExists) {
                $departure = $occupancyConnections[$i]->departure;
                $vehicle = $departure->vehicle->name;
                $from = $departure->station->{"@id"};

                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($vehicle, "."), 1);
                $occupancyURI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                if (!is_null($occupancyURI)) {
                    $occupancyArr = [];

                    $occupancyConnections[$i]->departure->occupancy = new \stdClass();
                    $occupancyConnections[$i]->departure->occupancy->{'@id'} = $occupancyURI;
                    $occupancyConnections[$i]->departure->occupancy->name = basename($occupancyURI);
                    array_push($occupancyArr, $occupancyURI);

                    if (isset($occupancyConnections[$i]->via)) {
                        foreach ($occupancyConnections[$i]->via as $key => $via) {
                            if ($key < count($occupancyConnections[$i]->via) - 1) {
                                $vehicleURI = 'http://irail.be/vehicle/' .
                                    substr(strrchr($occupancyConnections[$i]->via[$key + 1]->vehicle->name, "."), 1);
                            } else {
                                $vehicleURI = 'http://irail.be/vehicle/' .
                                    substr(strrchr($occupancyConnections[$i]->arrival->vehicle->name, "."), 1);
                            }

                            $from = $via->station->{'@id'};

                            $occupancyURI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                            $via->departure->occupancy = new \stdClass();
                            $via->departure->occupancy->{'@id'} = $occupancyURI;
                            $via->departure->occupancy->name = basename($occupancyURI);
                            array_push($occupancyArr, $occupancyURI);
                        }
                    }

                    $occupancyURI = OccupancyOperations::getMaxOccupancy($occupancyArr);

                    $occupancyConnections[$i]->occupancy = new \stdClass();
                    $occupancyConnections[$i]->occupancy->{'@id'} = $occupancyURI;
                    $occupancyConnections[$i]->occupancy->name = basename($occupancyURI);
                    $i++;
                } else {
                    $mongodbExists = false;
                }
            }
        } catch (Exception $e) {
            // Here one can implement a reporting to the iRail owner that the database has problems.
            return $connections;
        }

        return $occupancyConnections;
    }

    /**
     * @param array $trainRide
     * @return bool
     */
    private static function hasConnectionTrainDepartureBeenReported(array $trainRide): bool
    {
        return key_exists('dProgType', $trainRide['dep']) && $trainRide['dep']['dProgType'] == "REPORTED";
    }

    /**
     * @param array $trainRide
     * @return bool
     */
    private static function hasConnectionTrainArrivalBeenReported(array $trainRide): bool
    {
        return key_exists('aProgType', $trainRide['arr']) && $trainRide['arr']['aProgType'] == "REPORTED";
    }
}
