<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Database\LogDao;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\LiveboardV1Request;
use Irail\Http\Requests\LiveboardV2Request;
use Irail\Http\Responses\v2\LiveboardV2Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\LiveboardRepository;

class LiveboardV2Controller extends BaseIrailController
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

    public function getLiveboardById(LiveboardV2Request $request): JsonResponse
    {
        $this->validateStationId($request);
        $liveboardSearchResult = $this->liveboardRepository->getLiveboard($request);
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
        app(LogDao::class)->log(LogQueryType::LIVEBOARD, $query, $request->getUserAgent());
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
