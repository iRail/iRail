<?php

namespace Irail\Models\Dto\v1;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Models\DepartureAndArrival;
use Irail\Models\DepartureArrivalState;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\JourneyLegType;
use Irail\Models\Message;
use Irail\Models\PlatformInfo;
use Irail\Models\Result\JourneyPlanningSearchResult;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Models\Vehicle;
use stdClass;

class JourneyPlanningV1Converter extends V1Converter
{

    /**
     * @param IrailHttpRequest      $request
     * @param LiveboardSearchResult $result
     */
    public static function convert(JourneyPlanningRequest $request,
        JourneyPlanningSearchResult $journeyPlanning): DataRoot
    {
        $result = new DataRoot('connections');
        $result->connection = array_map(fn($jny) => self::convertJourneyPlan($jny), $journeyPlanning->getJourneys());
        return $result;
    }

    private static function convertJourneyPlan(Journey $journey)
    {
        $result = new StdClass();
        $result->departure = self::convertDeparture($journey->getDeparture(), $journey->getLegs()[0]);
        $result->arrival = self::convertArrival($journey->getArrival(), $journey->getLegs()[count($journey->getLegs()) - 1]);
        if (count($journey->getLegs()) > 1) {
            $result->via = self::convertVias($journey->getLegs());
        }
        $result->duration = $journey->getDurationSeconds();
        $result->remark = []; // TODO: implement
        $allAlertsInJourney = [];
        foreach ($journey->getLegs() as $leg) {
            foreach ($leg->getAlerts() as $alert) {
                $allAlertsInJourney[] = $alert;
            }
        }
        $result->alert = self::convertAlerts($allAlertsInJourney);
        return $result;
    }

    private static function convertDeparture(DepartureOrArrival $departure, JourneyLeg $departureLeg): StdClass
    {
        $result = new StdClass();
        $result->delay = $departure->getDelay();
        $result->station = self::convertStation($departure->getStation());
        $result->time = $departure->getScheduledDateTime()->getTimestamp();
        $result->vehicle = $departureLeg->getLegType() == JourneyLegType::JOURNEY
            ? self::convertVehicle($departure->getVehicle())
            : self::convertVehicle(new Vehicle("", "WALK", "", ""));
        $result->platform = self::convertPlatform($departure->getPlatform());
        $result->canceled = $departure->isCancelled() ? '1' : '0';
        $result->stop = array_map(fn($stop) => self::convertIntermediateStop($stop), $departureLeg->getIntermediateStops());
        $result->departureConnection = $departure->getDepartureUri();
        $result->direction = $departure->getDirection()->getName();
        $result->left = $departure->getStatus()?->hasLeft() ? '1' : '0';
        $result->walking = $departureLeg->getLegType() == JourneyLegType::WALKING ? '1' : '0';
        $result->alert = self::convertAlerts($departureLeg->getAlerts());
        return $result;
    }

    private static function convertArrival(DepartureOrArrival $arrival, JourneyLeg $arrivalLeg): StdClass
    {
        $result = new StdClass();
        $result->delay = $arrival->getDelay();
        $result->station = self::convertStation($arrival->getStation());
        $result->time = $arrival->getScheduledDateTime()->getTimestamp();
        $result->vehicle = $arrivalLeg->getLegType() == JourneyLegType::JOURNEY
            ? self::convertVehicle($arrival->getVehicle())
            : self::convertVehicle(new Vehicle("", "WALK", "", ""));
        $result->platform = self::convertPlatform($arrival->getPlatform());
        $result->canceled = $arrival->isCancelled() ? '1' : '0';
        $result->direction = $arrival->getDirection()->getName();
        $result->arrived = $arrival->getStatus()?->hasArrived() ? '1' : '0';
        $result->walking = $arrivalLeg->getLegType() == JourneyLegType::WALKING ? '1' : '0';
        return $result;
    }

    /**
     * @param JourneyLeg[] $legs
     * @return array
     */
    private static function convertVias(array $legs): array
    {
        $result = [];
        for ($i = 0; $i < count($legs) - 1; $i++) {
            $arrivingLeg = $legs[$i];
            $departingLeg = $legs[$i + 1];
            $via = new StdClass();
            $via->arrival = self::convertArrival($arrivingLeg->getArrival(), $arrivingLeg);
            $via->departure = self::convertDeparture($departingLeg->getDeparture(), $departingLeg);
            $via->timebetween = $departingLeg->getDeparture()->getRealtimeDateTime()->getTimestamp()
                - $arrivingLeg->getArrival()->getRealtimeDateTime()->getTimestamp();
            $via->station = self::convertStation($arrivingLeg->getArrival()->getStation());
            $via->vehicle = self::convertVehicle($departingLeg->getVehicle());
            $result[] = $via;
        }
        return $result;
    }

    private static function convertIntermediateStop(DepartureAndArrival $stop)
    {
        $result = new StdClass();
        $result->station = self::convertStation($stop->getStation());
        $result->scheduledArrivalTime = $stop->getArrival()->getScheduledDateTime()->getTimestamp();
        $result->arrivalCanceled = $stop->getArrival()->isCancelled() ? '1' : '0';
        $result->arrived =
            $stop->getArrival()->getStatus() == DepartureArrivalState::HALTING
            || $stop->getArrival()->getStatus() == DepartureArrivalState::LEFT;
        $result->scheduledDepartureTime = $stop->getDeparture()->getScheduledDateTime()->getTimestamp();
        $result->arrivalDelay = $stop->getArrival()->getDelay();
        $result->departureDelay = $stop->getDeparture()->getDelay();
        $result->departureCanceled = $stop->getDeparture()->isCancelled() ? '1' : '0';
        $result->left = $stop->getDeparture()->getStatus()?->hasLeft() ? '1' : '0';
        $result->arrived = $stop->getArrival()->getStatus()?->hasArrived() ? '1' : '0';
        $result->isExtraStop = $stop->getDeparture()->isExtra() || $stop->getArrival()->isExtra() ? '1' : '0';
        $result->platform = self::convertPlatform(new PlatformInfo(null, '?', false));
        return $result;
    }

    /**
     * @param Message[] $alerts
     * @return array
     */
    private static function convertAlerts(array $alerts): array
    {
        $result = [];
        foreach ($alerts as $message) {
            $alert = new StdClass();
            $alert->header = $message->getHeader();
            $alert->description = $message->getStrippedMessage();
            $alert->lead = $message->getLeadText();
            $alert->startTime = $message->getValidFrom()->getTimestamp();
            $alert->endTime = $message->getValidUpTo()->getTimestamp();
            $result[] = $alert;
        }
        return $result;
    }

}