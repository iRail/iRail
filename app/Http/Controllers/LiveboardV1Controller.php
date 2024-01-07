<?php

namespace Irail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Irail\Database\LogDao;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Models\Dto\v1\LiveboardV1Converter;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\Nmbs\NmbsHtmlLiveboardRepository;

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

    /** @noinspection PhpUnused */
    public function getLiveboardById(LiveboardV1Request $request): Response
    {
        Log::debug('Fetching liveboard for stop ' . $request->getStationId());
        if ($request->getDateTime()->isBefore(Carbon::now()->subHours(2))) {
            // Fallback to HTML data for older
            $repo = new NmbsHtmlLiveboardRepository(app(StationsRepository::class), app(CurlProxy::class));
        } else {
            $repo = app(LiveboardRepository::class);
        }
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
        app(LogDao::class)->log('Liveboard', $query, $request->getUserAgent());
    }
}
