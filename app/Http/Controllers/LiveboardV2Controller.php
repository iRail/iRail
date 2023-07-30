<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Http\Requests\LiveboardV2Request;
use Irail\Models\Dto\v2\LiveboardV2Converter;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\LiveboardRepository;

class LiveboardV2Controller extends BaseIrailController
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

    public function getLiveboardById(LiveboardV2Request $request): JsonResponse
    {
        $repo = app(LiveboardRepository::class);
        $liveboardSearchResult = $repo->getLiveboard($request);
        $dto = LiveboardV2Converter::convert($request, $liveboardSearchResult);
        $this->logRequest($request);
        return $this->outputJson($request, $dto);
    }

    /**
     * @param LiveboardV1Request $request
     * @return void
     */
    public function logRequest(LiveboardRequest $request): void
    {
        $query = [
            'station'  => $request->getStationId(),
            'language' => $request->getLanguage(),
            'version'  => 2
        ];
        app(LogRepository::class)->log('Liveboard', $query, $request->getUserAgent());
    }
}
