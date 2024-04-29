<?php

namespace Irail\Exceptions\Upstream;

use Throwable;

class UpstreamServerUnavailableException extends UpstreamServerException
{
    function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 504, $previous);
    }
}