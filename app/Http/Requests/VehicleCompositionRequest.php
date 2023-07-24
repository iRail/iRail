<?php

namespace Irail\Http\Requests;

interface VehicleCompositionRequest
{
    /**
     * @return string
     */
    public function getVehicleId(): string;
}