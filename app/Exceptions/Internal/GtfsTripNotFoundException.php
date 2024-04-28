<?php

namespace Irail\Exceptions\Internal;

class GtfsTripNotFoundException extends InternalProcessingException
{
    public function __construct(string $tripId)
    {
        parent::__construct(404, "Could not find trip '$tripId' in the GTFS data");
    }
}