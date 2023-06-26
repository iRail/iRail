<?php

namespace Irail\Models\Dto\v2;

use Irail\Models\DepartureOrArrival;
use Irail\Models\PlatformInfo;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;

class V2Converter
{

    public static function convertDepartureOrArrival(DepartureOrArrival $obj): array
    {
        return [
            'station'           => self::convertStation($obj->getStation()),
            'platform'          => self::convertPlatform($obj->getPlatform()),
            'vehicle'           => self::convertVehicle($obj->getVehicle()),
            'scheduledDateTime' => $obj->getScheduledDateTime(),
            'realtimeDateTime'  => $obj->getScheduledDateTime()->copy()->addSeconds($obj->getDelay()),
            'canceled'          => $obj->isCancelled(),
            'direction'         => self::convertVehicleDirection($obj->getDirection()),
            'status'            => $obj->getStatus()?->value,
            'isExtraTrain'      => $obj->isExtra()
        ];
    }

    public static function convertStation(StationInfo $obj): array
    {
        return [
            'id'            => $obj->getId(),
            'uri'           => $obj->getUri(),
            'name'          => $obj->getStationName(),
            'localizedName' => $obj->getLocalizedStationName(),
            'latitude'      => $obj->getLatitude(),
            'longitude'     => $obj->getLongitude()
        ];
    }

    public static function convertPlatform(PlatformInfo $obj): array
    {
        return [
            'designation' => $obj->getDesignation(),
            'hasChanged'  => $obj->hasChanged(),
        ];
    }

    public static function convertVehicle(Vehicle $obj)
    {
        return [
            'uri'    => $obj->getUri(),
            'id'     => $obj->getId(),
            'type'   => $obj->getType(),
            'number' => $obj->getNumber(),
        ];
    }

    private static function convertVehicleDirection(\Irail\Models\VehicleDirection $obj)
    {
        return [
            'name' => $obj->getName(),
            'station' => $obj->getStation() ? self::convertStation($obj->getStation()) : null
        ];
    }
}