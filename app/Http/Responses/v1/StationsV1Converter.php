<?php

namespace Irail\Http\Responses\v1;

use Irail\Http\Requests\StationsV1Request;
use Irail\Models\Station;

class StationsV1Converter extends V1Converter
{

    /**
     * @param StationsV1Request $request
     * @param Station[]         $stations
     * @return DataRoot
     */
    public static function convert(
        StationsV1Request $request,
        array $stations
    ): DataRoot {
        $result = new DataRoot('stations');
        usort($stations, fn(Station $a, Station $b) => strcmp($a->getLocalizedStationName(), $b->getLocalizedStationName()));
        $result->station = array_map(fn($station) => self::convertStation($station), $stations);
        return $result;
    }

}