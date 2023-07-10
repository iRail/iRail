<?php

namespace Irail\Models\Dto\v2;

use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\Message;
use Irail\Models\MessageLink;
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
        JourneyPlanningSearchResult $result): array
    {
        return [
            'journeys' => array_map(fn($journey) => self::convertJourney($journey), $result->getJourneys())
        ];
    }

    private static function convertJourney(Journey $journey): array
    {
        return [
            'departure' => self::convertDepartureOrArrival($journey->getDeparture()),
            'arrival'   => self::convertDepartureOrArrival($journey->getArrival()),
            'duration'  => $journey->getDurationSeconds(),
            'legs'      => array_map(fn($leg) => self::convertLeg($leg), $journey->getLegs()),
            'notes'     => $journey->getNotes(),
            'alerts'    => array_map(fn($note) => self::convertMessage($note), $journey->getServiceAlerts())
        ];
    }

    private static function convertLeg(JourneyLeg $leg): array
    {
        return [
            'type'      => $leg->getLegType(),
            'departure' => self::convertDepartureOrArrival($leg->getDeparture()),
            'arrival'   => self::convertDepartureOrArrival($leg->getArrival()),
            'duration'  => $leg->getDurationSeconds(),
            'vehicle'   => self::convertVehicle($leg->getVehicle()),
            'alerts'    => array_map(fn($note) => self::convertMessage($note), $leg->getAlerts()),
            'stops'     => $leg->getIntermediateStops(),
        ];
    }

    private static function convertMessage(Message $note): array
    {
        return [
            'id'           => $note->getId(),
            'header'       => $note->getHeader(),
            'lead'         => $note->getLeadText(),
            'link'         => array_map(fn($link) => self::convertMessageLink($link), $note->getLinks()),
            'validFrom'    => $note->getValidFrom(),
            'validUpTo'    => $note->getValidUpTo(),
            'lastModified' => $note->getLastModified()
        ];
    }

    private static function convertMessageLink(?MessageLink $link): ?array
    {
        if (!$link) {
            return null;
        }
        return [
            'action' => $link->getText(),
            'link'   => $link->getLink()
        ];
    }
}