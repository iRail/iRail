<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Models\Dto\v2\DatedVehicleJourneyV2Converter;
use Irail\Repositories\VehicleJourneyRepository;

class DatedVehicleJourneyV2Controller extends BaseIrailController
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

    public function getDatedVehicleJourney(DatedVehicleJourneyV2Request $request): JsonResponse
    {
        $repo = app(VehicleJourneyRepository::class);
        $vehicleJourneySearchResult = $repo->getDatedVehicleJourney($request);
        $dto = DatedVehicleJourneyV2Converter::convert($request, $vehicleJourneySearchResult);
        return $this->outputJson($request, $dto);
    }

}
