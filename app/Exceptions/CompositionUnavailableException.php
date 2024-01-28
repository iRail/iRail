<?php

namespace Irail\Exceptions;

class CompositionUnavailableException extends IrailHttpException
{

    /**
     * @param string $message
     */
    public function __construct(int $vehicleNumber, string $message = '')
    {
        parent::__construct(404, 'Composition for vehicle ' . $vehicleNumber . ' is unavailable.' . ($message ? " $message" : ''));
    }
}