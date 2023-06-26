<?php

namespace Irail\Models\Dto\v1;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\DepartureArrivalState;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Models\StationInfo;
use stdClass;

class LiveboardV1Converter extends V1Converter
{

    /**
     * @param IrailHttpRequest      $request
     * @param LiveboardSearchResult $result
     */
    public static function convert(LiveboardRequest $request,
        LiveboardSearchResult $liveboard): DataRoot
    {
        $result = new DataRoot('liveboard');
        $result->station = self::convertStation($liveboard->getStation());
        if ($request->getDepartureArrivalMode() == TimeSelection::DEPARTURE) {
            $result->departure = array_map(fn($dep) => self::convertDeparture($dep), $liveboard->getStops());
        } else {
            $result->arrival = array_map(fn($arr) => self::convertArrival($arr), $liveboard->getStops());
        }
        return $result;
    }

    private static function convertDeparture(DepartureOrArrival $departure)
    {
        $result = new StdClass();
        $result->station = self::convertStation($departure->getStation());
        $result->time = $departure->getScheduledDateTime()->getTimestamp();
        $result->delay = $departure->getDelay();
        $result->canceled = $departure->isCancelled() ? '1' : '0';
        $result->left = $departure->getStatus() == DepartureArrivalState::LEFT  ?'1' : '0';;
        $result->isExtra = $departure->isExtra() ? '1' : '0';;
        $result->vehicle = self::convertVehicle($departure->getVehicle());
        $result->platform = self::convertPlatform($departure->getPlatform());
        $result->departureConnection = $departure->getDepartureUri();
        return $result;
    }

    private static function convertArrival(DepartureOrArrival $arrival)
    {
        $result = new StdClass();
        $result->station = self::convertStation($arrival->getStation());
        $result->time = $arrival->getScheduledDateTime()->getTimestamp();
        $result->delay = $arrival->getDelay();
        $result->canceled = $arrival->isCancelled() ? '1' : '0';
        $result->arrived = $arrival->getStatus() == DepartureArrivalState::LEFT  ?'1' : '0';;
        $result->isExtra = $arrival->isExtra() ? '1' : '0';;
        $result->vehicle = self::convertVehicle($arrival->getVehicle());
        $result->platform = self::convertPlatform($arrival->getPlatform());
        $result->departureConnection = $arrival->getDepartureUri();
        return $result;
    }

}