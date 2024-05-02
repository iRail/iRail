<?php

namespace Irail\Exceptions\Upstream;

use Throwable;

class UpstreamParameterException extends UpstreamServerException
{
    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
