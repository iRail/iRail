<?php

namespace Irail\Exceptions\Request;

class RequestedStopNotFoundException extends InvalidRequestException
{
    public function __construct(string $stationIdOrName)
    {
        parent::__construct("Stop '$stationIdOrName' can not be found", 404);
    }
}
