<?php

namespace Irail\Models\Dto\v1;

use Irail\Models\StationInfo;
use stdClass;

abstract class V1Converter
{

    protected static function convertStation(StationInfo $station): StdClass
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

    protected static function convertPlatform(?\Irail\Models\PlatformInfo $platform): StdClass
    {
        $result = new StdClass();
        $result->name = $platform->getDesignation();
        $result->normal = $platform->hasChanged() ? '0' : '1';
        return $result;
    }

    protected static function convertVehicle(\Irail\Models\Vehicle $vehicle): StdClass
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