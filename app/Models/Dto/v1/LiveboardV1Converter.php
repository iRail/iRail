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

class LiveboardV1Converter
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

    private static function convertStation(StationInfo $station): StdClass
    {
        $obj = new StdClass();
        $obj->locationX = $station->getLongitude();
        $obj->locationY = $station->getLatitude();
        $obj->id = 'BE.NMBS.' . $station->getId();
        $obj->{'@id'} = $station->getUri();
        $obj->standardname = $station->getStationName();
        $obj->name = $station->getLocalizedStationName();
        return $obj;
    }

    private static function convertDeparture(DepartureOrArrival $departure)
    {
        $result = new StdClass();
        $result->station = self::convertStation($departure->getDirection());
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

    }

    private static function convertPlatform(?\Irail\Models\PlatformInfo $platform)
    {
        $result = new StdClass();
        $result->name = $platform->getDesignation();
        $result->normal = $platform->hasChanged() ? '0' : '1';
        return $result;
    }

    private static function convertVehicle(\Irail\Models\Vehicle $vehicle)
    {
        $result = new StdClass();
        $result->name = 'BE.NMBS.' . $vehicle->getName();
        $result->shortname = $vehicle->getName();
        $result->number = $vehicle->getNumber();
        $result->type = $vehicle->getType();
        $result->locationX = '0';
        $result->locationY = '0';
        $result->{'@id'} = $vehicle->getUri();
        return $result;
    }
}