<?php

namespace Irail\Exceptions\Upstream;

use Throwable;

class UpstreamRateLimitException extends UpstreamServerException
{
    function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 503, $previous);
    }
}