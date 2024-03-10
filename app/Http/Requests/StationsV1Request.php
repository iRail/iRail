<?php

namespace Irail\Http\Requests;

class StationsV1Request extends IrailHttpRequest implements IrailV1Request
{

    public function __construct()
    {
        // Only a language parameter, so no need for additional fields. Still its own class for clarity
        parent::__construct();
    }

}