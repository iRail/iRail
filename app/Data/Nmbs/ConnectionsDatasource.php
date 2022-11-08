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
use Dotenv\Dotenv;
use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\Connection;
use Irail\api\data\models\DepartureArrival;
use Irail\api\data\models\hafas\HafasConnectionLeg;
use Irail\api\data\models\hafas\HafasIntermediateStop;
use Irail\api\data\models\hafas\HafasVehicle;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Station;
use Irail\api\data\models\VehicleInfo;
use Irail\api\data\models\Via;
use Irail\api\data\models\ViaDepartureArrival;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\occupancy\OccupancyOperations;
use Irail\api\requests\ConnectionsRequest;
use stdClass;

class ConnectionsDatasource
{
    const TYPE_TRANSPORT_KEY_AUTOMATIC = 'automatic';
    const TYPE_TRANSPORT_KEY_NO_INTERNATIONAL_TRAINS = 'nointernationaltrains';
    const TYPE_TRANSPORT_KEY_TRAINS = 'trains';
    const TYPE_TRANSPORT_KEY_ALL = 'all';

    /**
     * This is the entry point for the data fetching and transformation.
     * @param DataRoot           $dataroot
     * @param ConnectionsRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, ConnectionsRequest $request): void
    {
        $from = $request->getFrom();
        if (count(explode('.', $request->getFrom())) > 1) {
            $from = StationsDatasource::getStationFromID($request->getFrom(), $request->getLang());
            $from = $from->name;
        }
        $to = $request->getTo();
        if (count(explode('.', $request->getTo())) > 1) {
            $to = StationsDatasource::getStationFromID($request->getTo(), $request->getLang());
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
     * @param string             $from The name of the origin station
     * @param string             $to The name of the destination station
     * @param string             $time The time, in hh:mm format
     * @param string             $date The date, in YYYYmmdd format
     * @param string             $lang The ISO2 language code, indicating in which language station names should be returned
     * @param string             $timeSel Whether to filter by departure or arrival time
     * @param string             $typeOfTransport The key identifying the types of transport which can be used
     * @param ConnectionsRequest $request
     * @return Connection[]
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
    ): array
    {
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
            $xml = self::requestHafasData($stations[0], $stations[1], $lang, $time, $date, $timeSel, $typeOfTransport);

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
     * @param string             $from
     * @param string             $to
     * @param string             $lang
     * @param ConnectionsRequest $request
     * @return array The two stations which were looked up
     * @throws Exception
     */
    private static function getStationsFromName(string $from, string $to, string $lang, ConnectionsRequest $request): array
    {
        try {
            $station1 = StationsDatasource::getStationFromName($from, $lang);
            $station2 = StationsDatasource::getStationFromName($to, $lang);

            if (isset($request)) {
                $request->setFrom($station1);
                $request->setTo($station2);
            }
            return [$station1, $station2];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * Get a key to identify this request. Requests which will result in a different response will receive a different key
     * @param string $idfrom
     * @param string $idto
     * @param string $lang
     * @param string $time
     * @param string $date
     * @param string $timeSel
     * @param string $typeOfTransport
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
    ): string
    {
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
     * @param string             $serverData
     * @param string             $lang
     * @param ConnectionsRequest $request
     * @return Connection[]
     * @throws Exception
     */
    public static function parseConnectionsAPI(string $serverData, string $lang, ConnectionsRequest $request): array
    {
        $json = json_decode($serverData, true);

        HafasCommon::throwExceptionOnInvalidResponse($json);

        $connections = [];
        foreach ($json['Trip'] as $conn) {
            $connections[] = self::parseHafasTrip(
                $request,
                $conn,
                $lang
            );
        }

        return $connections;
    }

    /**
     * @param ConnectionsRequest $request
     * @param array              $trip
     * @param string             $lang
     * @return Connection
     * @throws Exception
     */
    private static function parseHafasTrip(
        ConnectionsRequest $request,
        array $trip,
        string $lang
    ): Connection
    {
        $connection = new Connection();
        $connection->duration = Tools::transformDurationHHMMSS($trip['duration']);

        $legs = $trip['LegList']['Leg'];

        $firstDeparture = $legs[0]['Origin'];
        $departureStation = StationsDatasource::getStationFromID($firstDeparture['extId'], $lang);
        $connection->departure = new DepartureArrival();
        $connection->departure->station = $departureStation;

        // When a train has been cancelled mid-run, the arrival station can be different from the planned one!
        // Therefore, always parse it from the planner results
        $lastArrival = $legs[count($legs) - 1]['Destination'];
        $arrivalStation = StationsDatasource::getStationFromID($lastArrival['extId'], $lang);
        $connection->arrival = new DepartureArrival();
        $connection->arrival->station = $arrivalStation;

        $connection->departure->delay = self::calculateDelay($firstDeparture);
        $connection->departure->time = Tools::transformTime($firstDeparture['time'], $firstDeparture['date']);
        $connection->departure->platform = self::parseTrack($firstDeparture);

        $connection->arrival->delay = self::calculateDelay($lastArrival);
        $connection->arrival->time = Tools::transformTime($lastArrival['time'], $lastArrival['date']);
        $connection->arrival->platform = self::parseTrack($lastArrival);

        $trainsInConnection = self::parseTripLegs(
            $trip,
            $lang
        );

        $connection->departure->canceled = $trainsInConnection[0]->departure->canceled;
        $connection->arrival->canceled = end($trainsInConnection)->arrival->canceled;

        $vias = self::trainsAndWalksToIrailVias($trainsInConnection, $connection);

        // All the train alerts should go together in the connection alerts
        $connectionAlerts = [];
        foreach ($trainsInConnection as $train) {
            $connectionAlerts = array_merge($connectionAlerts, $train->alerts);
        }
        $connectionAlerts = array_unique($connectionAlerts, SORT_REGULAR);

        if (count($connectionAlerts) > 0) {
            $connection->alert = $connectionAlerts;
        }

        $connection->departure->vehicle = $trainsInConnection[0]->vehicle;

        $connection->departure->stop = $trainsInConnection[0]->stops;
        array_shift($connection->departure->stop);
        array_pop($connection->departure->stop);
        if (count($connection->departure->stop) === 0) {
            // TODO: Always include stops, even when empty, when clients support it
            unset($connection->departure->stop);
        }

        $connection->departure->departureConnection = 'http://irail.be/connections/' .
            substr(basename($departureStation->{'@id'}), 2) . '/' .
            date('Ymd', $connection->departure->time) . '/' .
            $trainsInConnection[0]->vehicle->shortname;

        $connection->departure->direction = $trainsInConnection[0]->direction;
        $connection->departure->left = $trainsInConnection[0]->left;

        $connection->departure->walking = 0;
        if (count($trainsInConnection[0]->alerts) > 0) {
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
     * Parse the arrival platform, and whether this is a normal platform or a changed one
     * @param array $departureOrArrival
     * @return Platform The platform for this departure.
     */
    private static function parseTrack(array $departureOrArrival): Platform
    {
        return self::parsePlatform($departureOrArrival, 'track', 'rtTrack');
    }

    /**
     * @param array  $data The data object containing the platform information, for example a departure or arrival.
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
        } else {
            if (key_exists($scheduledFieldName, $data)) {
                // Only scheduled data exists
                $result->name = $data[$scheduledFieldName];
                $result->normal = true;
            } else {
                // No data
                $result->name = "?";
                $result->normal = true;
            }
        }
        return $result;
    }


    /**
     * Parse all train objects in a connection (route/trip).
     * @param array  $trip The connection object for which trains should be parsed.
     * @param string $lang The language for station names etc.
     * @return HafasConnectionLeg[] All trains in this connection.
     * @throws Exception
     */
    private static function parseTripLegs(
        array $trip,
        string $lang
    ): array
    {
        $legs = [];
        // For the sake of code readability and maintainability: the response contains trains, not vias.
        // Therefore, just parse the trains and walks first, and create iRail via's based on the trains later.
        // This is way more readable compared to instantly creating the vias
        // Loop over all train rides in the list. This will also include the first train ride.
        foreach ($trip['LegList']['Leg'] as $leg) {
            $legs[] = self::parseHafasConnectionLeg(
                $leg,
                $trip,
                $lang
            );
        }
        return $legs;
    }

    /**
     * Parse a single train object in a connection (route/trip).
     * @param array  $leg The specific trainride to parse.
     * @param array  $trip The connection object for which trains should be parsed.
     * @param string $lang The language for station names etc.
     * @return HafasConnectionLeg The parsed leg
     * @throws Exception
     */
    private static function parseHafasConnectionLeg(
        array $leg,
        array $trip,
        string $lang
    ): HafasConnectionLeg
    {
        $legStart = $leg['Origin'];
        $legEnd = $leg['Destination'];

        $departurePlatform = self::parseTrack($legStart);
        $departureDelay = self::calculateDelay($legStart);
        $departureTime = Tools::transformTime(
            $legStart['time'],
            $legStart['date']
        );

        $arrivalTime = Tools::transformTime(
            $legEnd['time'],
            $legEnd['date']
        );
        $arrivalPlatform = self::parseTrack($legEnd);
        $arrivalDelay = self::calculateDelay($legEnd);

        $departureIsExtraStop = 0;
        $arrivalIsExtraStop = 0;
        if (key_exists('journeyStatus', $leg)) {
            // JourneyStatus:
            // - Planned (P)
            // - Replacement (R)
            // - Additional (A)
            // - Special (S)
            $departureIsExtraStop = $leg['journeyStatus'] == 'A';
            $arrivalIsExtraStop = $departureIsExtraStop;
        }

        $departureCancelled = false;
        $arrivalCancelled = false;

        if (key_exists('cancelled', $legStart)) {
            $departureCancelled = ($legStart['cancelled'] == true); // TODO: verify this
        }

        if (key_exists('cancelled', $legEnd)) {
            $arrivalCancelled = ($legEnd['cancelled'] == true); // TODO: verify this
        }
        $parsedTrain = new HafasConnectionLeg();

        $parsedTrain->departure = new ViaDepartureArrival();
        $parsedTrain->departure->time = $departureTime;
        $parsedTrain->departure->delay = $departureDelay;
        $parsedTrain->departure->platform = $departurePlatform;
        $parsedTrain->departure->canceled = $departureCancelled;
        $parsedTrain->departure->isExtraStop = $departureIsExtraStop;

        $parsedTrain->arrival = new ViaDepartureArrival();
        $parsedTrain->arrival->time = $arrivalTime;
        $parsedTrain->arrival->delay = $arrivalDelay;
        $parsedTrain->arrival->platform = $arrivalPlatform;
        $parsedTrain->arrival->canceled = $arrivalCancelled;
        $parsedTrain->arrival->isExtraStop = $arrivalIsExtraStop;

        $parsedTrain->duration = Tools::calculateSecondsHHMMSS(
            $arrivalTime,
            $legEnd['date'],
            $departureTime,
            $legStart['date']
        );

        // check if the departure has been reported
        if (self::hasDepartureOrArrivalBeenReported($legStart)) {
            $parsedTrain->left = 1;
        } else {
            $parsedTrain->left = 0;
        }

        // check if the arrival has been reported
        if (self::hasDepartureOrArrivalBeenReported($legEnd)) {
            $parsedTrain->arrived = 1;
            // A train can only arrive if it left first in the previous station
            $parsedTrain->left = 1;
        } else {
            $parsedTrain->arrived = 0;
        }

        $parsedTrain->departure->station = StationsDatasource::getStationFromID(
            $legStart['extId'],
            $lang
        );
        $parsedTrain->arrival->station = StationsDatasource::getStationFromID(
            $legEnd['extId'],
            $lang
        );

        $parsedTrain->isPartiallyCancelled = false;
        $parsedTrain->stops = [];
        $parsedTrain->alerts = [];
        $parsedTrain->stops = self::parseIntermediateStops($leg, $lang, $trip);
        if (count($parsedTrain->stops) > 0) {
            $parsedTrain->left = $parsedTrain->stops [0]->left;
        }

        $parsedTrain->alerts = HafasCommon::parseAlerts($leg);

        if ($leg['type'] == 'WALK') {
            // If the type is walking, there is no direction. Resolve this by hardcoding this variable.
            // TODO: This is ugly code, clean it up
            $parsedTrain->direction = new StdClass();
            $parsedTrain->direction->name = "WALK";
            $parsedTrain->vehicle = new StdClass();
            $parsedTrain->vehicle->name = 'WALK';
            $parsedTrain->walking = 1;
        } else {
            $parsedTrain->walking = 0;
            $parsedTrain->direction = new StdClass();
            if (key_exists('direction', $leg)) {
                // Get the direction from the API
                $parsedTrain->direction->name = $leg['direction'];
            } else {
                // If we can't load the direction from the data (direction is missing),
                // fill in the gap by using the furthest stop we know on this trains route.
                // This typically is the stop where the user leaves this train
                $parsedTrain->direction->name = end($parsedTrain->stops)->station->name;
            }
            $hafasVehicle = self::parseProduct($leg['Product']);
            $parsedTrain->vehicle = VehicleInfo::fromHafasVehicle($hafasVehicle);
        }
        return $parsedTrain;
    }

    /**
     * Parse an intermediate stop for a train on a connection. For example, if a traveller travels from
     * Brussels South to Brussels north, Brussels central would be an intermediate stop (the train stops but
     * the traveller stays on)
     * @param $lang
     * @param $rawIntermediateStop
     * @param $leg
     * @return HafasIntermediateStop The parsed intermediate stop.
     * @throws Exception
     */
    private static function parseHafasIntermediateStop($lang, $rawIntermediateStop, $leg)
    {
        $intermediateStop = new HafasIntermediateStop();
        $intermediateStop->station = StationsDatasource::getStationFromID(
            $rawIntermediateStop['extId'],
            $lang
        );

        if (key_exists('arrTime', $rawIntermediateStop)) {
            $intermediateStop->scheduledArrivalTime = Tools::transformTime(
                $rawIntermediateStop['arrTime'],
                $rawIntermediateStop['arrDate']
            );
        } else {
            $intermediateStop->scheduledArrivalTime = null;
        }

        if (key_exists('arrPrognosisType', $rawIntermediateStop)) {
            $intermediateStop->arrivalCanceled = HafasCommon::isArrivalCanceledBasedOnState($rawIntermediateStop['arrPrognosisType']);

            if ($rawIntermediateStop['arrPrognosisType'] == "REPORTED") {
                $intermediateStop->arrived = 1;
            } else {
                $intermediateStop->arrived = 0;
            }
        } else {
            $intermediateStop->arrivalCanceled = false;
            $intermediateStop->arrived = 0;
        }

        if (key_exists('rtArrTime', $rawIntermediateStop)) {
            $intermediateStop->arrivalDelay = Tools::calculateSecondsHHMMSS(
                $rawIntermediateStop['rtArrTime'],
                $rawIntermediateStop['rtArrDate'],
                $rawIntermediateStop['arrTime'],
                $rawIntermediateStop['arrDate']
            );
        } else {
            $intermediateStop->arrivalDelay = 0;
        }


        if (key_exists('depTime', $rawIntermediateStop)) {
            $intermediateStop->scheduledDepartureTime = Tools::transformTime(
                $rawIntermediateStop['depTime'],
                $rawIntermediateStop['depDate']
            );
        } else {
            $intermediateStop->scheduledDepartureTime = null;
        }

        if (key_exists('rtDepTime', $rawIntermediateStop)) {
            $intermediateStop->departureDelay = Tools::calculateSecondsHHMMSS(
                $rawIntermediateStop['rtDepTime'],
                $rawIntermediateStop['rtDepDate'],
                $rawIntermediateStop['depTime'],
                $rawIntermediateStop['depDate']
            );
        } else {
            $intermediateStop->departureDelay = 0;
        }

        if (key_exists('depPrognosisType', $rawIntermediateStop)) {
            $intermediateStop->departureCanceled =
                HafasCommon::isDepartureCanceledBasedOnState($rawIntermediateStop['depPrognosisType']);

            if ($rawIntermediateStop['depPrognosisType'] == "REPORTED") {
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
        if ($intermediateStop->scheduledDepartureTime == null) {
            $intermediateStop->scheduledDepartureTime = $intermediateStop->scheduledArrivalTime;
        }
        if ($intermediateStop->scheduledArrivalTime == null) {
            $intermediateStop->scheduledArrivalTime = $intermediateStop->scheduledDepartureTime;
        }

        // Some boolean about scheduled departure? First seen on an added stop
        // dInS, dInR, aOutS, aOutR are not processed at this moment
        if (key_exists('cancelledDeparture', $rawIntermediateStop)) {
            $intermediateStop->departureCanceled = 1;
        }

        if (key_exists('cancelledArrival', $rawIntermediateStop)) {
            $intermediateStop->arrivalCanceled = 1;
        }

        if (key_exists('additional', $rawIntermediateStop)) {
            $intermediateStop->isExtraStop = 1;
        } else {
            $intermediateStop->isExtraStop = 0;
        }

        // The last stop does not have a departure, and therefore we cannot construct a departure URI.
        if (key_exists('dProdX', $rawIntermediateStop)) {
            $intermediateStop->departureConnection = 'http://irail.be/connections/' .
                $rawIntermediateStop['extId'] . '/' .
                date('Ymd', $intermediateStop->scheduledDepartureTime) . '/' .
                str_replace(' ', '', $leg['Product']['Name']);
        }
        return $intermediateStop;
    }


    /**
     * @param $viaIndex int The index of the via which should be parsed
     * @param $trains array The trains to parse into vias
     * @return Via The parsed via
     */
    private static function parseViaFromTrainArray(int $viaIndex, array $trains): Via
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
            $constructedVia->departure->vehicle->name
        );
        $constructedVia->arrival->departureConnection = Tools::createDepartureUri(
            $constructedVia->station,
            $constructedVia->arrival->time,
            $constructedVia->arrival->vehicle->name
        );

        return $constructedVia;
    }

    /**
     * @param array $departureOrArrival
     * @return bool
     */
    private static function hasDepartureOrArrivalBeenReported(array $departureOrArrival): bool
    {
        // Origin and Destination have a prognosisType
        // PROGNOSED: prognosis for future
        // REPORTED: realtime data is recorded at a passed station
        // CORRECTED: Manually corrected data to ensure proper continuation where the train travels forward in time
        // CALCULATED: Calculated to fill gaps or for previously passed stations without a reported delay
        return key_exists('prognosisType', $departureOrArrival)
            && $departureOrArrival['prognosisType'] == 'REPORTED';
    }

    /**
     * @param array $departureOrArrival a departure or arrival object
     * @return int
     */
    public static function calculateDelay(array $departureOrArrival): int
    {
        if (!key_exists('rtTime', $departureOrArrival)) {
            return 0;
        }
        return Tools::calculateSecondsHHMMSS(
            $departureOrArrival['rtTime'],
            $departureOrArrival['rtDate'],
            $departureOrArrival['time'],
            $departureOrArrival['date']
        );
    }

    /**
     * @param array      $trainsInConnection
     * @param Connection $connection
     * @return array
     */
    public static function trainsAndWalksToIrailVias(array $trainsInConnection, Connection $connection): array
    {
        $viaCount = count($trainsInConnection) - 1;

        $vias = [];
        //check if there were vias at all. Ignore the first
        if ($viaCount != 0) {
            for ($viaIndex = 0; $viaIndex < $viaCount; $viaIndex++) {
                // Update the via array
                $vias[$viaIndex] = self::parseViaFromTrainArray($viaIndex, $trainsInConnection);
            }
            $connection->via = $vias;
        }
        return $vias;
    }


    /**
     * @param array  $leg
     * @param string $lang
     * @param array  $trip
     * @return array
     * @throws Exception
     */
    public static function parseIntermediateStops(array $leg, string $lang, array $trip): array
    {
        $parsedIntermediateStops = [];
        if (key_exists('Stops', $leg)) {
            $hafasIntermediateStops = $leg['Stops']['Stop']; // Yes this is correct, the arrays are weird in the source data
            foreach ($hafasIntermediateStops as $hafasIntermediateStop) {
                $intermediateStop = self::parseHafasIntermediateStop(
                    $lang,
                    $hafasIntermediateStop,
                    $trip
                );
                $parsedIntermediateStops[] = $intermediateStop;
            }

            // Sanity check: ensure that the arrived/left status for intermediate stops is correct.
            // If a train has reached the next intermediate stop, it must have passed the previous one.
            // Start at minus 2 because we "look forward" later in the loop
            for ($i = count($parsedIntermediateStops) - 2; $i >= 0; $i--) {
                if ($parsedIntermediateStops[$i + 1]->arrived) {
                    $parsedIntermediateStops[$i]->left = 1;
                    $parsedIntermediateStops[$i]->arrived = 1;
                }
            }
        }
        return $parsedIntermediateStops;
    }

    /**
     * @param $product
     * @return HafasVehicle
     */
    public static function parseProduct($product): HafasVehicle
    {
        $vehicle = new HafasVehicle();
        $vehicle->name = str_replace(" ", '', $product['name']);
        $vehicle->num = trim($product['num']);
        $vehicle->category = trim($product['catOutL']);
        return $vehicle;
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
        //TODO: make use of CommercialInfo.Occupancy.Level

        // Use this to track if the MongoDB module is set up. If not, it will be detected in the first iteration and
        // the occupancy score will not be returned.
        $mongodbExists = true;
        $i = 0;

        try {
            // TODO: migrate to optional SQL database
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
                "trip"          => substr($vias[$viaIndex]->vehicle->name, 8),
                "departureStop" => $departureStop->{'@id'},
                "arrivalStop"   => $arrivalStop->{'@id'}
            ];
            //set the next departureStop
            $departureStop = $vias[$viaIndex]->station;
        }
        //add last journey
        $journeyoptions["journeys"][] = [
            "trip"          => substr($connection->arrival->vehicle->name, 8),
            "departureStop" => $departureStop->{'@id'},
            "arrivalStop"   => $connection->arrival->station->{'@id'}
        ];

        $existing = $request->getJourneyOptions();
        $existing[] = $journeyoptions;
        $request->setJourneyOptions($existing);
    }
}
