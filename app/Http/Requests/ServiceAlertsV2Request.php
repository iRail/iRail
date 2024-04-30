<?php

namespace Irail\Http\Requests;

class ServiceAlertsV2Request extends IrailHttpRequest implements ServiceAlertsRequest
{
    use ServiceAlertCacheId;

    public function __construct()
    {
        parent::__construct();
    }
}
