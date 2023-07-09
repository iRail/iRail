<?php

namespace Irail\Models\Dto\v2;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Models\Result\VehicleJourneySearchResult;

class DatedVehicleJourneyV2Converter extends V2Converter
{

    /**
     * @param IrailHttpRequest           $request
     * @param VehicleJourneySearchResult $result
     * @return array
     */
    public static function convert(IrailHttpRequest $request,
        VehicleJourneySearchResult $result): array
    {
        return [
            'vehicle' => self::convertVehicle($result->getVehicle()),
            'stops'   => array_map(fn($obj) => self::convertDepartureAndArrival($obj), $result->getStops())
        ];
    }

    private static function convertDepartureAndArrival(\Irail\Models\DepartureAndArrival $departureAndArrival)
    {
        return [
            'arrival'   => self::convertDepartureOrArrival($departureAndArrival->getArrival()),
            'departure' => self::convertDepartureOrArrival($departureAndArrival->getDeparture()),
        ];
    }
}