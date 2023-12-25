<?php

namespace Irail\Models\Dto\v1;

use Irail\Http\Requests\DatedVehicleJourneyV1Request;
use Irail\Http\Requests\VehicleCompositionV1Request;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Result\VehicleJourneySearchResult;
use Irail\Models\VehicleComposition\RollingMaterialType;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use stdClass;

class VehicleCompositionV1Converter extends V1Converter
{

    /**
     * @param DatedVehicleJourneyV1Request $request
     * @param VehicleJourneySearchResult   $datedVehicleJourney
     * @return DataRoot
     */
    public static function convert(VehicleCompositionV1Request $request,
        VehicleCompositionSearchResult $searchResult): DataRoot
    {
        $result = new DataRoot('vehicleinformation');
        $result->segment = array_map(fn($segment) => self::convertSegment($segment), $searchResult->getSegments());
        return $result;
    }

    private static function convertSegment(TrainComposition $segment): StdClass
    {
        $result = new StdClass();
        $result->origin = self::convertStation($segment->getOrigin());
        $result->destination = self::convertStation($segment->getDestination());
        $result->composition = self::convertComposition($segment); // This is the old structure, don't break it
        return $result;
    }

    private static function convertComposition(TrainComposition $composition): StdClass
    {
        $result = new StdClass;
        $result->source = $composition->getCompositionSource();
        $result->unit = array_map(fn($unit) => self::convertUnit($unit), $composition->getUnits());
        return $result;
    }

    private static function convertUnit(TrainCompositionUnit $unit): StdClass
    {
        $result = new StdClass;
        $result->materialType = self::convertMaterialUnit($unit->getMaterialType());
        $result->hasToilets = $unit->hasToilet();
        $result->hasSecondClassOutlets = $unit->hasSecondClassOutlets();
        $result->hasFirstClassOutlets = $unit->hasFirstClassOutlets();
        $result->hasHeating = $unit->hasHeating();
        $result->hasAirco = $unit->hasAirco();
        $result->materialNumber = $unit->getMaterialNumber();
        $result->tractionType = $unit->getTractionType();
        $result->canPassToNextUnit = $unit->canPassToNextUnit();
        $result->seatsFirstClass = $unit->getSeatsFirstClass();
        $result->seatsCoupeFirstClass = $unit->getSeatsCoupeFirstClass();
        $result->standingPlacesFirstClass = $unit->getStandingPlacesFirstClass();
        $result->seatsSecondClass = $unit->getSeatsSecondClass();
        $result->seatsCoupeSecondClass = $unit->getSeatsCoupeSecondClass();
        $result->standingPlacesSecondClass = $unit->getStandingPlacesSecondClass();
        $result->lengthInMeter = $unit->getLengthInMeter();
        $result->hasSemiAutomaticInteriorDoors = $unit->hasSemiAutomaticInteriorDoors();
        $result->materialSubTypeName = $unit->getMaterialSubTypeName();
        $result->tractionPosition = $unit->getTractionPosition();
        $result->hasPrmSection = $unit->hasPrmSection();
        $result->hasPriorityPlaces = $unit->hasPriorityPlaces();
        $result->hasBikeSection = $unit->hasBikeSection();
        return $result;
    }

    private static function convertMaterialUnit(RollingMaterialType $materialType): stdClass
    {
        $result = new StdClass;
        $result->parent_type = $materialType->getParentType();
        $result->sub_type = $materialType->getSubType();
        $result->orientation = $materialType->getOrientation()->name;
        return $result;
    }

}