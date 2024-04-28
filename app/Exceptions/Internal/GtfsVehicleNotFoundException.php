<?php

namespace Irail\Exceptions\Internal;

class GtfsVehicleNotFoundException extends InternalProcessingException
{
    public function __construct(string $vehicleId)
    {
        parent::__construct(404, "Could not find vehicle '$vehicleId' in the GTFS data");
    }
}