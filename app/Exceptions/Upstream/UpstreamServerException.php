<?php

namespace Irail\Exceptions\Upstream;

use Irail\Exceptions\IrailHttpException;
use Throwable;

class UpstreamServerException extends IrailHttpException
{
    function __construct(string $message = '', int $code = 502, ?Throwable $previous = null)
    {
        parent::__construct($code, $message, $previous);
    }
}