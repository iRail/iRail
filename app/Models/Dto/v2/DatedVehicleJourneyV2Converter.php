<?php

namespace Irail\Models\Dto\v2;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Models\Dao\CompositionStatistics;
use Irail\Models\Result\VehicleJourneySearchResult;
use Irail\Models\VehicleComposition\TrainComposition;

class DatedVehicleJourneyV2Converter extends V2Converter
{

    /**
     * @param IrailHttpRequest           $request
     * @param VehicleJourneySearchResult $result
     * @param CompositionStatistics      $compositionStatistics
     * @return array
     */
    public static function convert(
        IrailHttpRequest $request,
        VehicleJourneySearchResult $result,
        TrainComposition $composition,
        CompositionStatistics $compositionStatistics
    ): array {
        return [
            'vehicle'               => self::convertVehicle($result->getVehicle()),
            'stops'                 => array_map(fn($obj) => self::convertDepartureAndArrival($obj), $result->getStops()),
            'composition'           => self::convertComposition($composition),
            'compositionStatistics' => self::convertCompositionStats($compositionStatistics)
        ];
    }

}