<?php

namespace Irail\Http\Requests;

use Carbon\Carbon;

interface VehicleCompositionRequest
{
    /**
     * @return string
     */
    public function getVehicleId(): string;

    /**
     * @return Carbon
     */
    public function getDateTime(): Carbon;
}
