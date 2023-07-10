<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\JourneyPlanningV2RequestImpl;
use Irail\Models\Dto\v2\JourneyPlanningV2Converter;
use Irail\Repositories\JourneyPlanningRepository;

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
        $journeyPlanningResult = $repo->getJourneyPlanning($request);
        $dto = JourneyPlanningV2Converter::convert($request, $journeyPlanningResult);
        return $this->outputJson($request, $dto);
    }

}
