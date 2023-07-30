<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Models\Dto\v1\LiveboardV1Converter;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\LiveboardRepository;

class LiveboardV1Controller extends BaseIrailController
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

    public function getLiveboardById(LiveboardV1Request $request): Response
    {
        Log::debug('Fetching liveboard for stop ' . $request->getStationId());
        $repo = app(LiveboardRepository::class);
        $liveboardSearchResult = $repo->getLiveboard($request);
        Log::debug('Found ' . count($liveboardSearchResult->getstops()) . ' entries for liveboard at stop ' . $request->getStationId());
        $dataRoot = LiveboardV1Converter::convert($request, $liveboardSearchResult);
        $this->logRequest($request);
        Log::debug('200 OK');
        return $this->outputV1($request, $dataRoot);
    }

    /**
     * @param LiveboardV1Request $request
     * @return void
     */
    public function logRequest(LiveboardV1Request $request): void
    {
        $query = [
            'station'       => $request->getStationId(),
            'language'      => $request->getLanguage(),
            'serialization' => $request->getResponseFormat(),
            'version'       => 1
        ];
        app(LogRepository::class)->log('Liveboard', $query, $request->getUserAgent());
    }
}
