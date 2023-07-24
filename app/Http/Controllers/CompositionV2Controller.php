<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Repositories\VehicleCompositionRepository;

class CompositionV2Controller extends IrailController
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

    public function getVehicleComposition(DatedVehicleJourneyV2Request $request): JsonResponse
    {
        $repo = app(VehicleCompositionRepository::class);
        $vehicleCompositionSearchResult = $repo->getComposition($request);
        $dto = VehiclecompositionV2Converter::convert($request, $vehicleCompositionSearchResult);
        return $this->outputJson($request, $dto);
    }

}
