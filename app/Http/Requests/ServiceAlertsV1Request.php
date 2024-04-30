<?php

namespace Irail\Http\Requests;

class ServiceAlertsV1Request extends IrailHttpRequest implements ServiceAlertsRequest, IrailV1Request
{
    use ServiceAlertCacheId;

    public function __construct()
    {
        parent::__construct();
    }
}
