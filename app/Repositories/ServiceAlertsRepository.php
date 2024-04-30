<?php

namespace Irail\Repositories;

use Irail\Http\Requests\ServiceAlertsRequest;
use Irail\Models\Result\ServiceAlertsResult;

interface ServiceAlertsRepository
{
    public function getServiceAlerts(ServiceAlertsRequest $request): ServiceAlertsResult;
}
