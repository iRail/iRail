<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Http\Requests\VehicleCompositionV2Request;
use Irail\Models\Dto\v2\VehicleCompositionV2Converter;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\VehicleCompositionRepository;

class CompositionV2Controller extends BaseIrailController
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

    public function getVehicleComposition(VehicleCompositionV2Request $request): JsonResponse
    {
        $repo = app(VehicleCompositionRepository::class);
        $vehicleCompositionSearchResult = $repo->getComposition($request);
        $dto = VehiclecompositionV2Converter::convert($request, $vehicleCompositionSearchResult);
        $this->logRequest($request);
        return $this->outputJson($request, $dto);
    }


    /**
     * @param VehicleCompositionV2Request $request
     * @return void
     */
    public function logRequest(VehicleCompositionV2Request $request): void
    {
        $query = [
            'id'       => $request->getVehicleId(),
            'language' => $request->getLanguage(),
            'version'  => 2
        ];
        app(LogRepository::class)->log('composition', $query, $request->getUserAgent());
    }
}
