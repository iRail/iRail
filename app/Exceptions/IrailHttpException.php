<?php

namespace Irail\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IrailHttpException extends HttpException
{
    public function __construct(int $statusCode, string $message = '', \Throwable $previous = null, array $headers = [])
    {
        parent::__construct($statusCode, $message, $previous, $headers, $statusCode);
    }
}
