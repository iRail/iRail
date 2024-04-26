<?php

namespace Irail\Exceptions;

class NoResultsException extends IrailHttpException
{
    public function __construct($message, $code = 500)
    {
        parent::__construct($code, $message);
    }
}