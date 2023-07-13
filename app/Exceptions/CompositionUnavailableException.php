<?php

namespace Irail\Exceptions;

class CompositionUnavailableException extends \Exception
{

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}