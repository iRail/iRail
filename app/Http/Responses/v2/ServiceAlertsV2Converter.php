<?php

namespace Irail\Http\Responses\v2;

use Irail\Http\Requests\ServiceAlertsRequest;
use Irail\Models\Result\ServiceAlertsResult;

class ServiceAlertsV2Converter extends V2Converter
{
    /**
     * @param ServiceAlertsRequest $request
     * @param ServiceAlertsResult  $result
     * @return array
     */
    public static function convert(
        ServiceAlertsRequest $request,
        ServiceAlertsResult $result): array
    {
        return [
            'alerts' => array_map(fn($obj) => self::convertMessage($obj), $result->getAlerts())
        ];
    }
}