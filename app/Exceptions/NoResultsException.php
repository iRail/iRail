<?php

namespace Irail\Exceptions;

use Exception;

class NoResultsException extends Exception
{
    public function __construct($message, $code = 500)
    {
        parent::__construct($code, $message);
    }
}