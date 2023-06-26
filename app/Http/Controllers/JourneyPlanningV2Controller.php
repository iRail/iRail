<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\JourneyPlanningV2RequestImpl;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Models\Dto\v2\LiveboardV2Converter;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\LiveboardRepository;

class JourneyPlanningV2Controller extends IrailController
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

    public function getJourneyPlanning(JourneyPlanningV2RequestImpl $request): JsonResponse
    {
        $repo = app(JourneyPlanningRepository::class);
        $liveboardSearchResult = $repo->getJourneyPlanning($request);
        $dto = LiveboardV2Converter::convert($request, $liveboardSearchResult);
        return $this->outputJson($request, $dto);
    }

}
