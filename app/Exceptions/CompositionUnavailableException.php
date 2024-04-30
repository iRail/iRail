<?php

namespace Irail\Exceptions;

class CompositionUnavailableException extends IrailHttpException
{
    /**
     * @param int|string $vehicleNumber
     * @param string     $message
     */
    public function __construct(int|string $vehicleNumber, string $message = '')
    {
        parent::__construct(404, 'Composition for vehicle ' . $vehicleNumber . ' is unavailable.' . ($message ? " $message" : ''));
    }
}
