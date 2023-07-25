<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\JourneyPlanningV1RequestImpl;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Models\Dto\v1\JourneyPlanningV1Converter;
use Irail\Models\Dto\v1\LiveboardV1Converter;
use Irail\Models\Dto\v2\LiveboardV2Converter;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\LiveboardRepository;

class JourneyPlanningV1Controller extends BaseIrailController
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

    public function getJourneyPlanning(JourneyPlanningV1RequestImpl $request): Response
    {
        $repo = app(JourneyPlanningRepository::class);
        $journeyPlanningResult = $repo->getJourneyPlanning($request);
        $dataRoot = JourneyPlanningV1Converter::convert($request, $journeyPlanningResult);
        return $this->outputV1($request, $dataRoot);
    }

}
