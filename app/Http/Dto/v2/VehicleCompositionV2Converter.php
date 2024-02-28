<?php

namespace Irail\Http\Dto\v2;

use Irail\Http\Requests\VehicleCompositionV2Request;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\VehicleComposition\RollingMaterialType;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionUnit;

class VehicleCompositionV2Converter extends V2Converter
{

    /**
     * @param VehicleCompositionV2Request    $request
     * @param VehicleCompositionSearchResult $searchResult
     * @return array
     */
    public static function convert(
        VehicleCompositionV2Request $request,
        VehicleCompositionSearchResult $searchResult
    ): array {
        return [
            'vehicle'     => self::convertVehicleWithoutDirection($searchResult->getVehicle()),
            'composition' => array_map(fn($segment) => self::convertSegment($segment), $searchResult->getSegments())
        ];
    }

    private static function convertSegment(TrainComposition $segment): array
    {
        return [
            'origin'      => self::convertStation($segment->getOrigin()),
            'destination' => self::convertStation($segment->getDestination()),
            'source'      => $segment->getCompositionSource(),
            'units'       => array_map(fn($unit) => self::convertUnit($unit), $segment->getUnits())
        ];
    }

    private static function convertUnit(TrainCompositionUnit $unit): array
    {
        return [
            'materialType '                  => self::convertMaterialUnit($unit->getMaterialType()),
            'uicCode '                       => $unit->getUicCode(),
            'hasToilets '                    => $unit->hasToilet(),
            'hasSecondClassOutlets '         => $unit->hasSecondClassOutlets(),
            'hasFirstClassOutlets '          => $unit->hasFirstClassOutlets(),
            'hasHeating '                    => $unit->hasHeating(),
            'hasAirco '                      => $unit->hasAirco(),
            'materialNumber '                => $unit->getMaterialNumber(),
            'tractionType '                  => $unit->getTractionType(),
            'canPassToNextUnit '             => $unit->canPassToNextUnit(),
            'seatsFirstClass '               => $unit->getSeatsFirstClass(),
            'seatsCoupeFirstClass '          => $unit->getSeatsCoupeFirstClass(),
            'standingPlacesFirstClass '      => $unit->getStandingPlacesFirstClass(),
            'seatsSecondClass '              => $unit->getSeatsSecondClass(),
            'seatsCoupeSecondClass '         => $unit->getSeatsCoupeSecondClass(),
            'standingPlacesSecondClass '     => $unit->getStandingPlacesSecondClass(),
            'lengthInMeter '                 => $unit->getLengthInMeter(),
            'hasSemiAutomaticInteriorDoors ' => $unit->hasSemiAutomaticInteriorDoors(),
            'materialSubTypeName '           => $unit->getMaterialSubTypeName(),
            'tractionPosition '              => $unit->getTractionPosition(),
            'hasPrmSection '                 => $unit->hasPrmSection(),
            'hasPriorityPlaces '             => $unit->hasPriorityPlaces(),
            'hasBikeSection '                => $unit->hasBikeSection()
        ];
    }

    private static function convertMaterialUnit(RollingMaterialType $materialType): array
    {
        return [
            'parentType'  => $materialType->getParentType(),
            'subType'     => $materialType->getSubType(),
            'orientation' => $materialType->getOrientation(),
        ];
    }

}