<?php

namespace Irail\Models\Dto\v2;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Models\Result\LiveboardSearchResult;

class LiveboardV2Converter
{

    /**
     * @param IrailHttpRequest      $request
     * @param LiveboardSearchResult $result
     */
    public static function convert(IrailHttpRequest $request,
        LiveboardSearchResult $result) : array
    {
        return (array) $result;
    }
}