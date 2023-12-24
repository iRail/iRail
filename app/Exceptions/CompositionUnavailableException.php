<?php

namespace Irail\Exceptions;

class CompositionUnavailableException extends \Exception
{

    /**
     * @param string $message
     */
    public function __construct(int $vehicleNumber, string $message = '')
    {
        parent::__construct('Composition for vehicle ' . $vehicleNumber . ' is unavailable. ' . $message, 404);
    }
}