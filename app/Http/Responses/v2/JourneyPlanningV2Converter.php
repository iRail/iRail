<?php

namespace Irail\Http\Responses\v2;

use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\Result\JourneyPlanningSearchResult;

class JourneyPlanningV2Converter extends V2Converter
{
    /**
     * @param JourneyPlanningRequest      $request
     * @param JourneyPlanningSearchResult $result
     * @return array
     */
    public static function convert(
        JourneyPlanningRequest $request,
        JourneyPlanningSearchResult $result
    ): array {
        return [
            'journeys' => array_map(fn ($journey) => self::convertJourney($journey), $result->getJourneys())
        ];
    }

    private static function convertJourney(Journey $journey): array
    {
        return [
            'departure' => self::convertDepartureOrArrival($journey->getDeparture()),
            'arrival'   => self::convertDepartureOrArrival($journey->getArrival()),
            'duration'  => $journey->getDurationSeconds(),
            'legs'      => array_map(fn ($leg) => self::convertLeg($leg), $journey->getLegs()),
            'notes'     => $journey->getNotes(),
            'alerts'    => array_map(fn ($note) => self::convertMessage($note), $journey->getServiceAlerts())
        ];
    }

    private static function convertLeg(JourneyLeg $leg): array
    {
        return [
            'type'                  => $leg->getLegType(),
            'departure'             => self::convertDepartureOrArrival($leg->getDeparture()),
            'arrival'               => self::convertDepartureOrArrival($leg->getArrival()),
            'duration'              => $leg->getDurationSeconds(),
            'vehicle'               => self::convertVehicle($leg->getVehicle()),
            'composition'           => array_map(fn ($composition) => self::convertComposition($composition), $leg->getComposition()),
            'compositionStatistics' => array_map(fn ($composition) => self::convertCompositionStats($composition), $leg->getCompositionStatsBySegment()),
            'alerts'                => array_map(fn ($note) => self::convertMessage($note), $leg->getAlerts()),
            'stops'                 => array_map(
                fn ($departureAndArrival) => self::convertDepartureAndArrival($departureAndArrival),
                $leg->getIntermediateStops()
            ),
        ];
    }
}
