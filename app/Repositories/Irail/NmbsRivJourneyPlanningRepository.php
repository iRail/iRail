<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 * This will return information about 1 specific route for the NMBS.
 *
 * fillDataRoot will fill the entire dataroot with connections
 */

namespace Irail\Repositories\Irail;

use Exception;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Models\CachedData;
use Irail\Models\DepartureAndArrival;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\JourneyLegType;
use Irail\Models\Result\JourneyPlanningSearchResult;
use Irail\Models\VehicleDirection;
use Irail\Repositories\Irail\traits\BasedOnHafas;
use Irail\Repositories\Irail\traits\TimeParser;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\Nmbs\Models\hafas\HafasVehicle;
use Irail\Repositories\Nmbs\StationsRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use stdClass;

class NmbsRivJourneyPlanningRepository implements JourneyPlanningRepository
{
    use BasedOnHafas;
    use TimeParser;

    private StationsRepository $stationsRepository;
    private NmbsRivRawDataRepository $rivDataRepository;


    public function __construct(StationsRepository $stationsRepository, NmbsRivRawDataRepository $rivDataRepository = null)
    {
        $this->stationsRepository = $stationsRepository;
        if ($rivDataRepository != null) {
            $this->rivDataRepository = $rivDataRepository;
        } else {
            $this->rivDataRepository = new NmbsRivRawDataRepository($this->stationsRepository);
        }
    }

    public function getJourneyPlanning(JourneyPlanningRequest $request): JourneyPlanningSearchResult
    {
        $data = $this->rivDataRepository->getRoutePlanningData($request);
        return $this->parseJourneyPlanning($request, $data);
    }

    private function parseJourneyPlanning(JourneyPlanningRequest $request, CachedData $data): JourneyPlanningSearchResult
    {
        $json = json_decode($data->getValue(), true);
        $this->throwExceptionOnInvalidResponse($json);

        $result = new JourneyPlanningSearchResult();
        $result->setCachedDataTimestamps($data->getCreatedAt(), $data->getExpiresAt());
        $result->setOriginStation($this->stationsRepository->getStationById($request->getOriginStationId()));
        $result->setDestinationStation($this->stationsRepository->getStationById($request->getDestinationStationId()));
        $connections = [];
        foreach ($json['Trip'] as $trip) {
            $connections[] = $this->parseHafasTrip(
                $request,
                $trip,
            );
        }
        $result->setJourneys($connections);
        return $result;
    }

    /**
     * @param JourneyPlanningRequest $request
     * @param array                  $trip
     * @return Journey
     * @throws Exception
     */
    private function parseHafasTrip(
        JourneyPlanningRequest $request,
        array $trip
    ): Journey {
        $connection = new Journey();
        $connection->setDurationSeconds($this->transformIso8601Duration($trip['duration']));

        $trainsInConnection = self::parseTripLegs(
            $trip,
            $request->getLanguage()
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
     * Parse all train objects in a connection (route/trip).
     * @param array  $trip The connection object for which trains should be parsed.
     * @param string $lang The language for station names etc.
     * @return JourneyLeg[] All trains in this connection.
     * @throws Exception
     */
    private static function parseTripLegs(
        array $trip,
        string $lang
    ): array {
        $legs = [];
        // For the sake of code readability and maintainability: the response contains trains, not vias.
        // Therefore, just parse the trains and walks first, and create iRail via's based on the trains later.
        // This is way more readable compared to instantly creating the vias
        // Loop over all train rides in the list. This will also include the first train ride.
        foreach ($trip['LegList']['Leg'] as $leg) {
            $legs[] = $this->parseHafasConnectionLeg(
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
     * @return JourneyLeg The parsed leg
     * @throws Exception
     */
    private function parseHafasConnectionLeg(
        array $leg,
        array $trip,
        string $lang
    ): JourneyLeg {
        $parsedLeg = new JourneyLeg();

        $legStart = $leg['Origin'];
        $legEnd = $leg['Destination'];

        $departure = $this->parseConnectionLegEnd($legStart, $lang);
        $arrival = $this->parseConnectionLegEnd($legEnd, $lang);

        if (key_exists('journeyStatus', $leg)) {
            // JourneyStatus:
            // - Planned (P)
            // - Replacement (R)
            // - Additional (A)
            // - Special (S)
            $departure->setIsExtra($leg['journeyStatus'] == 'A');
            $arrival->setIsExtra($departure->isExtra());
        }
        // check if the departure has been reported
        if (self::hasDepartureOrArrivalBeenReported($legStart)) {
            $departure->setIsReported(true);
        }

        // check if the arrival has been reported
        if (self::hasDepartureOrArrivalBeenReported($legEnd)) {
            $arrival->setIsReported(true);
            // A train can only arrive if it left first in the previous station
            $departure->setIsReported(true);
        }

        if (key_exists('cancelled', $legStart)) {
            $departure->setIsCancelled($legStart['cancelled'] == true); // TODO: verify this
        }

        if (key_exists('cancelled', $legEnd)) {
            $arrival->setIsCancelled($legEnd['cancelled'] == true); // TODO: verify this
        }

        $parsedLeg->setDeparture($departure);
        $parsedLeg->setArrival($arrival);

        $intermediateStops = $this->parseIntermediateStops($trip, $leg, $lang);
        $parsedLeg->setIntermediateStops($intermediateStops);
        $parsedLeg->setAlerts($this->parseAlerts($leg));

        if ($leg['type'] == 'WALK') {
            // If the type is walking, there is no direction.
            $parsedLeg->setLegType(JourneyLegType::WALKING);
        } else {
            $parsedLeg->setLegType(JourneyLegType::JOURNEY);
            $direction = new VehicleDirection();
            if (key_exists('direction', $leg)) {
                // Get the direction from the API
                $direction->setName($leg['direction']);
            } else {
                // If we can't load the direction from the data (direction is missing),
                // fill in the gap by using the furthest stop we know on this trains route.
                // This typically is the stop where the user leaves this train
                $direction->setName(end($intermediateStops)->station->name);
            }
            $parsedLeg->setDirection($direction);
            $hafasVehicle = $this->parseProduct($leg['Product']);
            $parsedLeg->setVehicle($hafasVehicle->toVehicle());
        }
        return $parsedLeg;
    }


    /**
     * @param array  $leg
     * @param string $lang
     * @param array  $trip
     * @return DepartureAndArrival[];
     * @throws Exception
     */
    public function parseIntermediateStops(array $trip, array $leg, string $lang): array
    {
        $parsedIntermediateStops = [];
        if (key_exists('Stops', $leg)) {
            $hafasIntermediateStops = $leg['Stops']['Stop']; // Yes this is correct, the arrays are weird in the source data
            foreach ($hafasIntermediateStops as $hafasIntermediateStop) {
                $intermediateStop = $this->parseHafasIntermediateStop(
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
     * Parse an intermediate stop for a train on a connection. For example, if a traveller travels from
     * Brussels South to Brussels north, Brussels central would be an intermediate stop (the train stops but
     * the traveller stays on)
     * @param $lang
     * @param $rawIntermediateStop
     * @param $leg
     * @return HafasIntermediateStop The parsed intermediate stop.
     * @throws Exception
     */
    private function parseHafasIntermediateStop($lang, $rawIntermediateStop, $leg)
    {
        $intermediateStop = new HafasIntermediateStop();
        $intermediateStop->station = StationsDatasource::getStationFromID(
            $rawIntermediateStop['extId'],
            $lang
        );

        if (key_exists('arrTime', $rawIntermediateStop)) {
            $intermediateStop->scheduledArrivalTime = $this->transformTime(
                $rawIntermediateStop['arrTime'],
                $rawIntermediateStop['arrDate']
            );
        } else {
            $intermediateStop->scheduledArrivalTime = null;
        }

        if (key_exists('arrPrognosisType', $rawIntermediateStop)) {
            $intermediateStop->arrivalCanceled = HafasCommon::isArrivalCanceledBasedOnState($rawIntermediateStop['arrPrognosisType']);

            if ($rawIntermediateStop['arrPrognosisType'] == 'REPORTED') {
                $intermediateStop->arrived = 1;
            } else {
                $intermediateStop->arrived = 0;
            }
        } else {
            $intermediateStop->arrivalCanceled = false;
            $intermediateStop->arrived = 0;
        }

        if (key_exists('rtArrTime', $rawIntermediateStop)) {
            $intermediateStop->arrivalDelay = $this->parseDurationFromTwoDatesAndTimes(
                $rawIntermediateStop['rtArrTime'],
                $rawIntermediateStop['rtArrDate'],
                $rawIntermediateStop['arrTime'],
                $rawIntermediateStop['arrDate']
            );
        } else {
            $intermediateStop->arrivalDelay = 0;
        }


        if (key_exists('depTime', $rawIntermediateStop)) {
            $intermediateStop->scheduledDepartureTime = $this->parseDateAndTime(
                $rawIntermediateStop['depTime'],
                $rawIntermediateStop['depDate']
            );
        } else {
            $intermediateStop->scheduledDepartureTime = null;
        }

        if (key_exists('rtDepTime', $rawIntermediateStop)) {
            $intermediateStop->departureDelay = $this->parseDurationFromTwoDatesAndTimes(
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

            if ($rawIntermediateStop['depPrognosisType'] == 'REPORTED') {
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
     * @param array $departureOrArrival
     * @return bool
     */
    private function hasDepartureOrArrivalBeenReported(array $departureOrArrival): bool
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
    public function calculateDelay(array $departureOrArrival): int
    {
        if (!key_exists('rtTime', $departureOrArrival)) {
            return 0;
        }
        return $this->parseDurationInSeconds(
            $departureOrArrival['date'], $departureOrArrival['time'],
            $departureOrArrival['rtDate'], $departureOrArrival['rtTime']
        );
    }

    /**
     * @param $product
     * @return HafasVehicle
     */
    public function parseProduct($product): HafasVehicle
    {
        $vehicle = new HafasVehicle();
        $vehicle->name = str_replace(' ', '', $product['name']);
        $vehicle->num = trim($product['num']);
        $vehicle->category = trim($product['catOutL']);
        return $vehicle;
    }

    /**
     * @param mixed  $legStartOrEnd
     * @param string $lang
     * @return DepartureOrArrival
     * @throws InternalProcessingException|UnknownStopException
     */
    public function parseConnectionLegEnd(array $legStartOrEnd, string $lang): DepartureOrArrival
    {
        $departureOrArrival = new DepartureOrArrival();
        $departureOrArrival->setStation(StationsDatasource::getStationFromID(
            $legStartOrEnd['extId'],
            $lang
        ));
        $departureOrArrival->setScheduledDateTime($this->parseDateAndTime(
            $legStartOrEnd['time'],
            $legStartOrEnd['date']
        ));
        $departureOrArrival->setDelay($this->calculateDelay($legStartOrEnd));
        $departureOrArrival->setPlatform($this->parsePlatform($legStartOrEnd));
        return $departureOrArrival;
    }

    /**
     * Add spitsgids occupancy data to the response
     *
     * @param $connections
     * @param $date
     * @return array
     */
    private function addOccupancy($connections, $date): array
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
                $from = $departure->station->{'@id'};

                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($vehicle, '.'), 1);
                $occupancyURI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                if (!is_null($occupancyURI)) {
                    $occupancyArr = [];

                    $occupancyConnections[$i]->departure->occupancy = new stdClass();
                    $occupancyConnections[$i]->departure->occupancy->{'@id'} = $occupancyURI;
                    $occupancyConnections[$i]->departure->occupancy->name = basename($occupancyURI);
                    array_push($occupancyArr, $occupancyURI);

                    if (isset($occupancyConnections[$i]->via)) {
                        foreach ($occupancyConnections[$i]->via as $key => $via) {
                            if ($key < count($occupancyConnections[$i]->via) - 1) {
                                $vehicleURI = 'http://irail.be/vehicle/' .
                                    substr(strrchr($occupancyConnections[$i]->via[$key + 1]->vehicle->name, '.'), 1);
                            } else {
                                $vehicleURI = 'http://irail.be/vehicle/' .
                                    substr(strrchr($occupancyConnections[$i]->arrival->vehicle->name, '.'), 1);
                            }

                            $from = $via->station->{'@id'};

                            $occupancyURI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                            $via->departure->occupancy = new stdClass();
                            $via->departure->occupancy->{'@id'} = $occupancyURI;
                            $via->departure->occupancy->name = basename($occupancyURI);
                            array_push($occupancyArr, $occupancyURI);
                        }
                    }

                    $occupancyURI = OccupancyOperations::getMaxOccupancy($occupancyArr);

                    $occupancyConnections[$i]->occupancy = new stdClass();
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
     * @param JourneyPlanningRequest $request
     * @param                        $connection
     * @param                        $vias
     */
    private function storeIrailLogData($request, $connection, $vias): void
    {
        //Add journey options to the logs of iRail
        $journeyoptions = ['journeys' => []];
        $departureStop = $connection->departure->station;
        for ($viaIndex = 0; $viaIndex < count($vias); $viaIndex++) {
            $arrivalStop = $vias[$viaIndex]->station;
            $journeyoptions['journeys'][] = [
                'trip'          => substr($vias[$viaIndex]->vehicle->name, 8),
                'departureStop' => $departureStop->{'@id'},
                'arrivalStop'   => $arrivalStop->{'@id'}
            ];
            //set the next departureStop
            $departureStop = $vias[$viaIndex]->station;
        }
        //add last journey
        $journeyoptions['journeys'][] = [
            'trip'          => substr($connection->arrival->vehicle->name, 8),
            'departureStop' => $departureStop->{'@id'},
            'arrivalStop'   => $connection->arrival->station->{'@id'}
        ];

        $existing = $request->getJourneyOptions();
        $existing[] = $journeyoptions;
        $request->setJourneyOptions($existing);
    }

}
