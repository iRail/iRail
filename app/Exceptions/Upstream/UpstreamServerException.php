<?php

namespace Irail\Exceptions\Upstream;

use Exception;
use Throwable;

class UpstreamServerException extends Exception
{
    function __construct(string $message = "", $code = 504, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}