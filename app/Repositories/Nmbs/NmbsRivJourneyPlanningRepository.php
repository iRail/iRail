<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 * This will return information about 1 specific route for the NMBS.
 *
 * fillDataRoot will fill the entire dataroot with connections
 */

namespace Irail\Repositories\Nmbs;

use DateTime;
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
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Models\Result\JourneyPlanningSearchResult;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;
use Irail\Models\VehicleDirection;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\Nmbs\Traits\BasedOnHafas;
use Irail\Repositories\Nmbs\Traits\TimeParser;
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
     * Parse notes for a deserialized raw trip.
     * @param array $trip
     * @return String[] the notes for this trip
     */
    private static function parseNotes(array $trip): array
    {
        /*
        "Notes": {
         "Note": [
           {
               "value": "Due to delays a connecting service may not be reachable.",
             "key": "text.realtime.connection.brokentrip",
             "type": "R"
           }
         ]
        }
   "value": "Deze aansluiting kan waarschijnlijk niet gehaald worden.",
                  "key": "text.realtime.journey.missed.connection",
                  "type": "R"

            "value": "De aansluiting van S44 5159 zal worden gegarandeerd zolang de vertraging minder is dan 4 minuten.",
            "key": "WAITING",
            "type": "I"
        */
        $notes = [];
        if (key_exists('Notes', $trip)) {
            foreach ($trip['Notes']['Note'] as $note) {
                $notes[] = $note['value'];
            }
        }
        return $notes;
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
        $json = $this->deserializeAndVerifyResponse($data->getValue());

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
        array $trip
    ): Journey
    {
        $connection = new Journey();


        $trainsInConnection = self::parseTripLegs(
            $trip,
            $request->getLanguage()
        );

        $connection->setLegs($trainsInConnection);
        $connection->setNotes(self::parseNotes($trip));
        $connection->setServiceAlerts($this->parseAlerts($trip));

        if ($connection->getDurationSeconds() != $this->transformIso8601Duration($trip['duration'])) {
            Log::warning('Duration does not match for connection. A possible parsing error has occured!');
        }

        return $connection;
    }

    /**
     * Parse all train objects in a connection (route/trip).
     * @param array  $trip The connection object for which trains should be parsed.
     * @param string $lang The language for station names etc.
     * @return JourneyLeg[] All trains in this connection.
     * @throws Exception
     */
    private function parseTripLegs(
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
    ): JourneyLeg
    {
        $legStart = $leg['Origin'];
        $legEnd = $leg['Destination'];

        $departure = $this->parseConnectionLegEnd($legStart);
        $arrival = $this->parseConnectionLegEnd($legEnd);

        // When a previous leg gets delayed, a following leg may become unreachable if there is insufficient time to transfer in the station
        $reachable = $leg['reachable'];

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
        $departure->setIsReported(self::hasDepartureOrArrivalBeenReported($legStart));

        // check if the arrival has been reported
        if (self::hasDepartureOrArrivalBeenReported($legEnd)) {
            $arrival->setIsReported(true);
            // A train can only arrive if it left first in the previous station
            $departure->setIsReported(true);
        } else {
            $arrival->setIsReported(false);
        }

        if (key_exists('cancelled', $legStart)) {
            $departure->setIsCancelled($legStart['cancelled'] == true); // TODO: verify this
        }

        if (key_exists('cancelled', $legEnd)) {
            $arrival->setIsCancelled($legEnd['cancelled'] == true); // TODO: verify this
        }

        $parsedLeg = new JourneyLeg($departure, $arrival);
        $parsedLeg->setReachable($reachable);
        $parsedLeg->setAlerts($this->parseAlerts($leg));

        if ($leg['type'] == 'WALK') {
            // If the type is walking, there is no direction.
            $parsedLeg->setLegType(JourneyLegType::WALKING);
            $parsedLeg->setVehicle(null);
        } else {
            $parsedLeg->setLegType(JourneyLegType::JOURNEY);

            $vehicle = $this->parseProduct($leg['Product'])->toVehicle();

            $intermediateStops = $this->parseIntermediateStops($trip, $leg, $lang, $vehicle);
            $parsedLeg->setIntermediateStops($intermediateStops);

            if (key_exists('direction', $leg)) {
                // Get the direction from the API
                $directionName = $leg['direction'];
            } else {
                // If we can't load the direction from the data (direction is missing),
                // fill in the gap by using the furthest stop we know on this trains route.
                // This typically is the stop where the user leaves this train
                $directionName = end($intermediateStops)->getStation()->getStationName();
            }
            $directionLastStationNames = explode('&', $directionName);
            $directionStation = $this->stationsRepository->findStationByName(end($directionLastStationNames));
            $vehicle->setDirection(new VehicleDirection($directionName, $directionStation));
            $parsedLeg->setVehicle($vehicle);
        }

        // TODO: set occupancy data for intermediate stops
        $parsedLeg->getDeparture()->setOccupancy(
            $this->getOccupancy(
                $legStart,
                $parsedLeg->getDeparture()->getStation(),
                $parsedLeg->getDeparture()->getVehicle(),
                $parsedLeg->getDeparture()->getScheduledDateTime()
            )
        );

        return $parsedLeg;
    }


    /**
     * @param array   $trip
     * @param array   $leg
     * @param string  $lang
     * @param Vehicle $vehicle
     * @return DepartureAndArrival[];
     * @throws InternalProcessingException
     * @throws UnknownStopException
     */
    public function parseIntermediateStops(array $trip, array $leg, string $lang, Vehicle $vehicle): array
    {
        if (!key_exists('Stops', $leg)) {
            return [];
        }

        $parsedIntermediateStops = [];
        $hafasIntermediateStops = $leg['Stops']['Stop']; // Yes this is correct, the arrays are weird in the source data
        // The first and last stop are not intermediate stops, skip them
        for ($i = 1; $i < count($hafasIntermediateStops) - 1; $i++) {
            $hafasIntermediateStop = $hafasIntermediateStops[$i];
            $intermediateStop = $this->parseHafasIntermediateStop(
                $this->stationsRepository,
                $hafasIntermediateStop,
                $vehicle
            );
            $parsedIntermediateStops[] = $intermediateStop;
        }
        $this->fixInconsistentReportedStates($parsedIntermediateStops);

        return $parsedIntermediateStops;
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
     * @param mixed $legStartOrEnd
     * @return DepartureOrArrival
     * @throws InternalProcessingException|UnknownStopException
     */
    public function parseConnectionLegEnd(array $legStartOrEnd): DepartureOrArrival
    {
        $departureOrArrival = new DepartureOrArrival();
        $departureOrArrival->setStation($this->stationsRepository->getStationByHafasId(
            $legStartOrEnd['extId']
        ));
        $departureOrArrival->setScheduledDateTime($this->parseDateAndTime(
            $legStartOrEnd['date'],
            $legStartOrEnd['time']
        ));
        $departureOrArrival->setDelay($this->calculateDelay($legStartOrEnd));
        $departureOrArrival->setPlatform($this->parsePlatform($legStartOrEnd));
        return $departureOrArrival;
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param array       $rawDepartureOrArrival The raw NMBS data from which to extract official data
     * @param StationInfo $station
     * @param Vehicle     $vehicle
     * @param DateTime    $date
     * @return OccupancyInfo
     */
    private function getOccupancy(array $rawDepartureOrArrival, StationInfo $station, Vehicle $vehicle, DateTime $date): OccupancyInfo
    {
        // TODO: implement spitsgids
        $officialLevel = OccupancyLevel::UNKNOWN;
        if (array_key_exists('CommercialInfo', $rawDepartureOrArrival)
            && array_key_exists('Occupancy', $rawDepartureOrArrival['CommercialInfo'])) {
            $officialLevel = OccupancyLevel::fromNmbsLevel($rawDepartureOrArrival['CommercialInfo']['Occupancy']['Level']);
        }
        return new OccupancyInfo($officialLevel, OccupancyLevel::UNKNOWN);
    }
}
