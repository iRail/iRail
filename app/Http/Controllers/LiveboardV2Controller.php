<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Database\LogDao;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Http\Requests\LiveboardV2Request;
use Irail\Models\Dto\v2\LiveboardV2Converter;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsHtmlLiveboardRepository;

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
        $this->validateStationId($request);
        $repo = new NmbsHtmlLiveboardRepository(app(StationsRepository::class), app(CurlProxy::class));
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
        app(LogDao::class)->log('Liveboard', $query, $request->getUserAgent());
    }

    /**
     * @param LiveboardV2Request $request
     * @return void
     */
    public function validateStationId(LiveboardV2Request $request): void
    {
        $stationRepo = app(StationsRepository::class);
        try {
            $stationRepo->getStationById($request->getStationId());
        } catch (UnknownStopException $e) {
            throw new UnknownStopException(400, 'Unknown stop id ' . $request->getStationId()
                . '. Please check your query.');
        }
    }
}
