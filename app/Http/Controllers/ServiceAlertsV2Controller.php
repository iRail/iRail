<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\ServiceAlertsV2Request;
use Irail\Models\Dto\v2\ServiceAlertsV2Converter;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\ServiceAlertsRepository;

class ServiceAlertsV2Controller extends BaseIrailController
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

    public function getServiceAlerts(ServiceAlertsV2Request $request): JsonResponse
    {
        $repo = app(ServiceAlertsRepository::class);
        $serviceAlertsResult = $repo->getServiceAlerts($request);
        $dto = ServiceAlertsV2Converter::convert($request, $serviceAlertsResult);
        $this->logRequest($request);
        return $this->outputJson($request, $dto);
    }

    /**
     * @param ServiceAlertsV2Request $request
     * @return void
     */
    public function logRequest(ServiceAlertsV2Request $request): void
    {
        $query = [
            'language'      => $request->getLanguage(),
            'version'       => 2
        ];
        app(LogRepository::class)->log('Disturbances', $query, $request->getUserAgent());
    }
}
