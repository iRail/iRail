<?php

namespace Irail\Models\Dto\v2;

use Irail\Models\DepartureOrArrival;
use Irail\Models\Message;
use Irail\Models\MessageLink;
use Irail\Models\PlatformInfo;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;
use Irail\Models\VehicleDirection;

class V2Converter
{

    public static function convertDepartureAndArrival(\Irail\Models\DepartureAndArrival $departureAndArrival): array
    {
        return [
            'arrival'   => self::convertDepartureOrArrival($departureAndArrival->getArrival()),
            'departure' => self::convertDepartureOrArrival($departureAndArrival->getDeparture()),
        ];
    }

    public static function convertDepartureOrArrival(?DepartureOrArrival $obj): ?array
    {
        if ($obj == null) {
            return null;
        }
        return [
            'station'           => self::convertStation($obj->getStation()),
            'platform'          => self::convertPlatform($obj->getPlatform()),
            'vehicle'           => self::convertVehicle($obj->getVehicle()),
            'scheduledDateTime' => $obj->getScheduledDateTime(),
            'realtimeDateTime'  => $obj->getRealtimeDateTime(),
            'canceled'          => $obj->isCancelled(),
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
            'uri'       => $obj->getUri(),
            'id'        => $obj->getId(),
            'type'      => $obj->getType(),
            'number'    => $obj->getNumber(),
            'direction' => self::convertVehicleDirection($obj->getDirection())
        ];
    }

    private static function convertVehicleDirection(VehicleDirection $obj)
    {
        return [
            'name'    => $obj->getName(),
            'station' => $obj->getStation() ? self::convertStation($obj->getStation()) : null
        ];
    }


    protected static function convertMessage(Message $note): array
    {
        return [
            'id'           => $note->getId(),
            'header'       => $note->getHeader(),
            'lead'         => $note->getLeadText(),
            'message'      => $note->getMessage(),
            'plainText'    => $note->getStrippedMessage(),
            'link'         => array_map(fn($link) => self::convertMessageLink($link), $note->getLinks()),
            'validFrom'    => $note->getValidFrom(),
            'validUpTo'    => $note->getValidUpTo(),
            'lastModified' => $note->getLastModified()
        ];
    }

    protected static function convertMessageLink(?MessageLink $link): ?array
    {
        if (!$link) {
            return null;
        }
        return [
            'text' => $link->getText(),
            'link' => $link->getLink(),
            'language' => $link->getLanguage()
        ];
    }
}