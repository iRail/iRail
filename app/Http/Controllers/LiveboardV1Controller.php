<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Irail\Database\LogDao;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Http\Responses\v1\LiveboardV1Converter;
use Irail\Repositories\LiveboardRepository;

class LiveboardV1Controller extends BaseIrailController
{
    private LiveboardRepository $liveboardRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(LiveboardRepository $liveboardRepository)
    {
        $this->liveboardRepository = $liveboardRepository;
    }

    /** @noinspection PhpUnused */
    public function getLiveboardById(LiveboardV1Request $request): Response
    {
        Log::debug('Fetching liveboard for stop ' . $request->getStationId());
        $liveboardSearchResult = $this->liveboardRepository->getLiveboard($request);
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
        app(LogDao::class)->log('Liveboard', $query, $request->getUserAgent());
    }
}
