<?php

namespace Irail\Models\Dto\v1;

use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Models\PlatformInfo;
use Irail\Models\Station;
use Irail\Models\Vehicle;
use stdClass;

abstract class V1Converter
{

    protected static function convertStation(Station $station): StdClass
    {
        $obj = new StdClass();
        $obj->locationX = $station->getLongitude();
        $obj->locationY = $station->getLatitude();
        $obj->id = 'BE.NMBS.' . $station->getId();
        $obj->name = $station->getLocalizedStationName();
        $obj->{'@id'} = $station->getUri();
        $obj->standardname = $station->getStationName();
        return $obj;
    }

    protected static function convertPlatform(?PlatformInfo $platform): StdClass
    {
        $result = new StdClass();
        $result->name = $platform ? $platform->getDesignation() : '?';
        $result->normal = !$platform || $platform->hasChanged() ? '0' : '1';
        return $result;
    }

    protected static function convertVehicle(Vehicle $vehicle): StdClass
    {
        $result = new StdClass();
        $result->name = 'BE.NMBS.' . $vehicle->getType() . $vehicle->getNumber();
        $result->shortname = $vehicle->getType() . ' ' . $vehicle->getNumber();
        $result->number = $vehicle->getNumber();
        $result->type = $vehicle->getType();
        $result->locationX = '0';
        $result->locationY = '0';
        $result->{'@id'} = $vehicle->getUri();
        return $result;
    }

    protected static function convertWalk(): StdClass
    {
        $result = new StdClass();
        $result->name = 'BE.NMBS.WALK';
        $result->shortname = 'WALK';
        $result->number = '';
        $result->type = '';
        $result->locationX = '0';
        $result->locationY = '0';
        $result->{'@id'} = '';
        return $result;
    }

    protected static function convertOccupancy(OccupancyInfo $occupancy)
    {
        $level = $occupancy->getSpitsgidsLevel() != OccupancyLevel::UNKNOWN
            ? $occupancy->getSpitsgidsLevel()
            : $occupancy->getOfficialLevel();
        $result = new stdClass();
        $result->{'@id'} = $level->value;
        $result->name = basename($level->value);
        return $result;
    }
}