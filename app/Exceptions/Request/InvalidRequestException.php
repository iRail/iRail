<?php

namespace Irail\Exceptions\Request;

use Irail\Exceptions\IrailHttpException;

class InvalidRequestException extends IrailHttpException
{
    public function __construct($message, $code = 400)
    {
        parent::__construct($code, $message);
    }
}
