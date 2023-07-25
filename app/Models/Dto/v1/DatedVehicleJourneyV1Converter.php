<?php

namespace Irail\Models\Dto\v1;

use Irail\Http\Requests\DatedVehicleJourneyV1Request;
use Irail\Models\DepartureAndArrival;
use Irail\Models\DepartureArrivalState;
use Irail\Models\Result\VehicleJourneySearchResult;
use stdClass;

class DatedVehicleJourneyV1Converter extends V1Converter
{

    /**
     * @param DatedVehicleJourneyV1Request $request
     * @param VehicleJourneySearchResult   $datedVehicleJourney
     * @return DataRoot
     */
    public static function convert(DatedVehicleJourneyV1Request $request,
        VehicleJourneySearchResult $datedVehicleJourney): DataRoot
    {
        $result = new DataRoot('vehicleinformation');
        $result->vehicle = self::convertVehicle($datedVehicleJourney->getVehicle());
        $result->stop = array_map(fn($stop) => self::convertDeparture($stop), $datedVehicleJourney->getStops());
        return $result;
    }

    private static function convertDeparture(DepartureAndArrival $stop)
    {
        $departure = $stop->getDeparture() ?: $stop->getArrival();
        $arrival = $stop->getArrival() ?: $stop->getDeparture();

        $result = new StdClass();
        $result->station = self::convertStation($stop->getStation());
        $result->time = $departure->getScheduledDateTime()->getTimestamp();
        $result->platform = self::convertPlatform($departure->getPlatform());
        $result->scheduledDepartureTime = $departure->getScheduledDateTime()->getTimestamp();
        $result->scheduledArrivalTime = $arrival->getScheduledDateTime()->getTimestamp();
        $result->delay = $departure->getDelay();
        $result->canceled = $departure->isCancelled() ? '1' : '0';
        $result->departureDelay = $stop->getDeparture() ? $stop->getDeparture()->getDelay() : 0;
        $result->departureCanceled = $departure->isCancelled() ? '1' : '0';
        $result->arrivalDelay = $stop->getArrival() ? $stop->getArrival()->getDelay() : 0;
        $result->arrivalCanceled = $arrival->isCancelled() ? '1' : '0';
        $result->left = $departure->getStatus()?->hasLeft() ? '1' : '0';
        $result->arrived = ($arrival->getStatus() == DepartureArrivalState::HALTING
            || $arrival->getStatus() == DepartureArrivalState::LEFT) ? '1' : '0';

        $result->isExtraStop = ($departure->isExtra()) || ($arrival->isExtra()) ? '1' : '0';
        if ($stop->getDeparture()) {
            $result->occupancy = self::convertOccupancy($stop->getDeparture()->getOccupancy());
            $result->departureConnection = $departure->getDepartureUri();
        }

        return $result;
    }

}