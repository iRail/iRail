<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\ServiceAlertsV1Request;
use Irail\Models\Dto\v1\ServiceAlertsV1Converter;
use Irail\Repositories\ServiceAlertsRepository;

class ServiceAlertsV1Controller extends IrailController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getServiceAlerts(ServiceAlertsV1Request $request): Response
    {
        $repo = app(ServiceAlertsRepository::class);
        $serviceAlertsResult = $repo->getServiceAlerts($request);
        $dataRoot = ServiceAlertsV1Converter::convert($request, $serviceAlertsResult);
        return $this->outputV1($request, $dataRoot);
    }

}
