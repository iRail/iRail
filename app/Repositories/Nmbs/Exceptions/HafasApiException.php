<?php

namespace Irail\Repositories\Nmbs\Exceptions;

use Irail\Exceptions\Upstream\UpstreamServerException;
use Throwable;

class HafasApiException extends UpstreamServerException
{
    public function __construct(string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}