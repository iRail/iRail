<?php

namespace Irail\Http\Responses\v2;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Models\Result\LiveboardSearchResult;

class LiveboardV2Converter extends V2Converter
{
    /**
     * @param IrailHttpRequest      $request
     * @param LiveboardSearchResult $result
     */
    public static function convert(
        IrailHttpRequest $request,
        LiveboardSearchResult $result
    ): array
    {
        return [
            'station' => self::convertStation($result->getStation()),
            'stops'   => array_map(fn ($obj) => self::convertDepartureOrArrival($obj), $result->getStops())
        ];
    }
}
