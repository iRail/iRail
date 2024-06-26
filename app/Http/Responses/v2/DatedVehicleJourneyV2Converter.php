<?php

namespace Irail\Http\Responses\v2;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Models\Dao\CompositionStatistics;
use Irail\Models\Result\VehicleJourneySearchResult;
use Irail\Models\VehicleComposition\TrainComposition;

class DatedVehicleJourneyV2Converter extends V2Converter
{
    /**
     * @param IrailHttpRequest           $request
     * @param VehicleJourneySearchResult $result
     * @param TrainComposition[]         $composition
     * @param CompositionStatistics[] $compositionStatistics
     * @return array
     */
    public static function convert(
        IrailHttpRequest $request,
        VehicleJourneySearchResult $result,
        array $composition,
        array $compositionStatistics
    ): array {
        return [
            'vehicle'               => self::convertVehicle($result->getVehicle()),
            'stops'                 => array_map(fn ($obj) => self::convertDepartureAndArrival($obj), $result->getStops()),
            'composition'           => array_map(fn ($segment) => self::convertComposition($segment), $composition),
            'compositionStatistics' => array_map(fn ($statistics) => self::convertCompositionStats($statistics), $compositionStatistics)
        ];
    }
}
