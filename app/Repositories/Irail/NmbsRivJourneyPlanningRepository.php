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
use Illuminate\Support\Facades\Log;
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
use Irail\Models\Vehicle;
use Irail\Models\VehicleDirection;
use Irail\Repositories\Irail\traits\BasedOnHafas;
use Irail\Repositories\Irail\traits\TimeParser;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\Nmbs\Models\hafas\HafasVehicle;
use Irail\Repositories\Nmbs\StationsRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;

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

    /**
     * @throws Exception
     */
    public function getJourneyPlanning(JourneyPlanningRequest $request): JourneyPlanningSearchResult
    {
        $data = $this->rivDataRepository->getRoutePlanningData($request);
        return $this->parseJourneyPlanning($request, $data);
    }

    /**
     * @throws Exception
     */
    private function parseJourneyPlanning(JourneyPlanningRequest $request, CachedData $data): JourneyPlanningSearchResult
    {
        $json = json_decode($data->getValue(), true);
        $this->throwExceptionOnInvalidResponse($json);

        $result = new JourneyPlanningSearchResult();
        $result->mergeCacheValidity($data->getCreatedAt(), $data->getExpiresAt());
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
        array                  $trip
    ): Journey
    {
        $connection = new Journey();


        $trainsInConnection = self::parseTripLegs(
            $trip,
            $request->getLanguage()
        );

        $connection->setLegs($trainsInConnection);
        $connection->setNotes([]); // TODO: parse alerts

        if ($connection->getDurationSeconds() != $this->transformIso8601Duration($trip['duration'])) {
            Log::warning('Duration does not match for connection. A possible parsing error has occured!');
        }

        return $connection;
    }

    /**
     * Parse all train objects in a connection (route/trip).
     * @param array $trip The connection object for which trains should be parsed.
     * @param string $lang The language for station names etc.
     * @return JourneyLeg[] All trains in this connection.
     * @throws Exception
     */
    private function parseTripLegs(
        array  $trip,
        string $lang
    ): array
    {
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

        $parsedLeg->setAlerts($this->parseAlerts($leg));

        if ($leg['type'] == 'WALK') {
            // If the type is walking, there is no direction.
            $parsedLeg->setLegType(JourneyLegType::WALKING);
        } else {
            $parsedLeg->setLegType(JourneyLegType::JOURNEY);

            $vehicle = $this->parseProduct($leg['Product'])->toVehicle();
            $parsedLeg->setVehicle($vehicle);
            $intermediateStops = $this->parseIntermediateStops($trip, $leg, $lang, $vehicle);
            $parsedLeg->setIntermediateStops($intermediateStops);

            $direction = new VehicleDirection();
            if (key_exists('direction', $leg)) {
                // Get the direction from the API
                $direction->setName($leg['direction']);
            } else {
                // If we can't load the direction from the data (direction is missing),
                // fill in the gap by using the furthest stop we know on this trains route.
                // This typically is the stop where the user leaves this train
                $direction->setName(end($intermediateStops)->getStation()->getStationName());
            }
            $parsedLeg->setDirection($direction);
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
    public function parseIntermediateStops(array $trip, array $leg, string $lang, Vehicle $vehicle): array
    {
        if (!key_exists('Stops', $leg)) {
            return [];
        }

        $parsedIntermediateStops = [];
        $hafasIntermediateStops = $leg['Stops']['Stop']; // Yes this is correct, the arrays are weird in the source data
        foreach ($hafasIntermediateStops as $hafasIntermediateStop) {
            $intermediateStop = $this->parseHafasIntermediateStop(
                $lang,
                $hafasIntermediateStop,
                $vehicle
            );
            $parsedIntermediateStops[] = $intermediateStop;
        }

        // Sanity check: ensure that the arrived/left status for intermediate stops is correct.
        // If a train has reached the next intermediate stop, it must have passed the previous one.
        // Start at end position minus 2 because we "look forward" in the loop
        for ($i = count($parsedIntermediateStops) - 2; $i >= 0; $i--) {
            if ($parsedIntermediateStops[$i + 1]->arrived) {
                $parsedIntermediateStops[$i]->left = 1;
                $parsedIntermediateStops[$i]->arrived = 1;
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
     * @param Vehicle $vehicle
     * @return DepartureAndArrival The parsed intermediate stop.
     * @throws InternalProcessingException
     * @throws UnknownStopException
     */
    private function parseHafasIntermediateStop($lang, $rawIntermediateStop, Vehicle $vehicle): DepartureAndArrival
    {
        $intermediateStop = new DepartureAndArrival();
        $station = StationsDatasource::getStationFromID(
            $rawIntermediateStop['extId'],
            $lang
        );

        if (key_exists('arrTime', $rawIntermediateStop)) {
            $arrival = new DepartureOrArrival();
            $arrival->setStation($station);
            $arrival->setVehicle($vehicle);
            $arrival->setScheduledDateTime($this->parseDateAndTime(
                $rawIntermediateStop['arrTime'],
                $rawIntermediateStop['arrDate']
            ));
            if (key_exists('arrPrognosisType', $rawIntermediateStop)) {
                $arrival->setIsCancelled($this->isArrivalCanceledBasedOnState($rawIntermediateStop['arrPrognosisType']));
                $arrival->setIsReported($rawIntermediateStop['arrPrognosisType'] == 'REPORTED');
            }
            if (key_exists('rtArrTime', $rawIntermediateStop)) {
                $arrival->setDelay($this->getSecondsBetweenTwoDatesAndTimes(
                    $rawIntermediateStop['rtArrTime'],
                    $rawIntermediateStop['rtArrDate'],
                    $rawIntermediateStop['arrTime'],
                    $rawIntermediateStop['arrDate']
                ));
            }
            $arrival->setIsCancelled(key_exists('cancelledArrival', $rawIntermediateStop));
            $arrival->setIsExtra(key_exists('additional', $rawIntermediateStop));
            $intermediateStop->setArrival($arrival);
        }

        if (key_exists('depTime', $rawIntermediateStop)) {
            $departure = new DepartureOrArrival();
            $departure->setStation($station);
            $departure->setVehicle($vehicle);
            $departure->setScheduledDateTime($this->parseDateAndTime(
                $rawIntermediateStop['depTime'],
                $rawIntermediateStop['depDate']
            ));
            if (key_exists('depPrognosisType', $rawIntermediateStop)) {
                $departure->setIsCancelled($this->isDepartureCanceledBasedOnState($rawIntermediateStop['depPrognosisType']));
                $departure->setIsReported($rawIntermediateStop['depPrognosisType'] == 'REPORTED');
            }
            if (key_exists('rtArrTime', $rawIntermediateStop)) {
                $departure->setDelay($this->getSecondsBetweenTwoDatesAndTimes(
                    $rawIntermediateStop['rtArrTime'],
                    $rawIntermediateStop['rtArrDate'],
                    $rawIntermediateStop['depTime'],
                    $rawIntermediateStop['depDate']
                ));
            }
            $departure->setIsCancelled(key_exists('cancelledDeparture', $rawIntermediateStop));
            $departure->setIsExtra(key_exists('additional', $rawIntermediateStop));
            $intermediateStop->setDeparture($departure);
        }

        // Some boolean about scheduled departure? First seen on an added stop
        // dInS, dInR, aOutS, aOutR are not processed at this moment
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
     * @throws InternalProcessingException
     */
    public function calculateDelay(array $departureOrArrival): int
    {
        if (!key_exists('rtTime', $departureOrArrival)) {
            return 0;
        }
        return $this->getSecondsBetweenTwoDatesAndTimes(
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
        return new HafasVehicle(trim($product['num']), trim($product['catOutL']));
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
}
