<?php

namespace Irail\Exceptions\Upstream;

use Exception;
use Throwable;

class UpstreamServerException extends Exception
{
    function __construct(string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, 504, $previous);
    }
}