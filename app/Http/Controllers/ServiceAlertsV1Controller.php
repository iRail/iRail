<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Http\Requests\ServiceAlertsV1Request;
use Irail\Models\Dto\v1\ServiceAlertsV1Converter;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\ServiceAlertsRepository;

class ServiceAlertsV1Controller extends BaseIrailController
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
        $this->logRequest($request);
        return $this->outputV1($request, $dataRoot);
    }

    /**
     * @param ServiceAlertsV1Request $request
     * @return void
     */
    public function logRequest(ServiceAlertsV1Request $request): void
    {
        $query = [
            'language'      => $request->getLanguage(),
            'serialization' => $request->getResponseFormat(),
            'version'       => 1
        ];
        app(LogRepository::class)->log('Disturbances', $query, $request->getUserAgent());
    }
}
