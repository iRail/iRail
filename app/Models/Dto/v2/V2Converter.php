<?php

namespace Irail\Models\Dto\v2;

use Irail\Models\Dao\CompositionStatistics;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Message;
use Irail\Models\MessageLink;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Models\PlatformInfo;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\TrainCompositionOnSegment;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
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
            'occupancy'         => self::convertOccupancy($obj->getOccupancy()),
            /*'isExtraTrain'      => $obj->isExtra()*/
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
            'type'         => $note->getType()->name,
            'header'       => $note->getHeader(),
            'lead'         => $note->getLeadText(),
            'message'      => $note->getMessage(),
            'plainText'    => $note->getStrippedMessage(),
            'links'        => array_map(fn($link) => self::convertMessageLink($link), $note->getLinks()),
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
            'text'     => $link->getText(),
            'link'     => $link->getLink(),
            'language' => $link->getLanguage()
        ];
    }

    private static function convertOccupancy(OccupancyInfo $occupancy): array
    {
        return [
            'official'  => self::convertOccupancyLevel($occupancy->getOfficialLevel()),
            'spitsgids' => self::convertOccupancyLevel($occupancy->getSpitsgidsLevel()),
        ];
    }

    /**
     * @param OccupancyLevel $occupancy
     * @return array
     */
    public static function convertOccupancyLevel(OccupancyLevel $occupancy): array
    {
        return [
            'value' => $occupancy->name,
            'uri'   => $occupancy->value
        ];
    }

    protected static function convertComposition(TrainCompositionOnSegment $composition): array
    {
        return [
            'fromStationId' => $composition->getOrigin()->getId(),
            'toStationId'   => $composition->getDestination()->getId(),
            'units'         => array_map(
                fn($unit, $index) => self::convertCompositionUnit($index, $unit),
                $composition->getComposition()->getUnits(),
                array_keys($composition->getComposition()->getUnits())
            )
        ];
    }

    private static function convertCompositionUnit(int $index, TrainCompositionUnit $unit): array
    {
        return [
            'id'                            => $index,
            'materialType'                  => [
                'parent_type' => $unit->getMaterialType()->getParentType(),
                'sub_type'    => $unit->getMaterialType()->getSubType(),
                'orientation' => $unit->getMaterialType()->getOrientation()->name
            ],
            'uicCode'                       => $unit->getUicCode(),
            'materialSubTypeName'           => $unit->getMaterialSubTypeName(),
            'materialNumber'                => $unit->getMaterialNumber(),
            'tractionType'                  => $unit->getTractionType(),
            'hasToilets'                    => $unit->hasToilet(),
            'hasPrmToilets'                 => $unit->hasToilet(),
            'hasTables'                     => $unit->hasTables(),
            'hasSecondClassOutlets'         => $unit->hasSecondClassOutlets(),
            'hasFirstClassOutlets'          => $unit->hasFirstClassOutlets(),
            'hasHeating'                    => $unit->hasHeating(),
            'hasAirco'                      => $unit->hasAirco(),
            'canPassToNextUnit'             => $unit->canPassToNextUnit(),
            'standingPlacesSecondClass'     => $unit->getStandingPlacesSecondClass(),
            'standingPlacesFirstClass'      => $unit->getStandingPlacesFirstClass(),
            'seatsCoupeSecondClass'         => $unit->getSeatsCoupeSecondClass(),
            'seatsCoupeFirstClass'          => $unit->getSeatsCoupeFirstClass(),
            'seatsSecondClass'              => $unit->getSeatsSecondClass(),
            'seatsFirstClass'               => $unit->getSeatsFirstClass(),
            'lengthInMeter'                 => $unit->getLengthInMeter(),
            'hasSemiAutomaticInteriorDoors' => $unit->hasSemiAutomaticInteriorDoors(),
            'hasLuggageSection'             => $unit->hasLuggageSection(),
            'tractionPosition'              => $unit->getTractionPosition(),
            'hasPrmSection'                 => $unit->hasPrmSection(),
            'hasPriorityPlaces'             => $unit->hasPriorityPlaces(),
            'hasBikeSection'                => $unit->hasBikeSection()
        ];
    }

    protected static function convertCompositionStats(CompositionStatistics $compositionStatistics): array
    {
        return [
            'historicalRecords'                      => $compositionStatistics->getNumberOfRecords(),
            'medianLength'                           => $compositionStatistics->getMedianProbableLength(),
            'mostFrequentLength'                     => $compositionStatistics->getMostProbableLength(),
            'mostFrequentLengthOccurrencePercentage' => $compositionStatistics->getMostProbableLengthOccurrence(),
            'mostFrequentType'                       => $compositionStatistics->getMostProbableType(),
            'mostFrequentTypeOccurencePercentage'    => $compositionStatistics->getMostProbableTypeOccurrence(),
        ];
    }
}