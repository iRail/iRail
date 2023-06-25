<?php

namespace Irail\Models\Dto\v1;

use Irail\Http\Requests\IrailHttpRequest;
use Irail\Models\Result\LiveboardSearchResult;

class LiveboardV1Converter
{

    /**
     * @param IrailHttpRequest      $request
     * @param LiveboardSearchResult $result
     */
    public static function convert(IrailHttpRequest $request,
        LiveboardSearchResult $result) : DataRoot
    {
        return [];
    }
}