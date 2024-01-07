<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Database\HistoricCompositionDao;
use Irail\Database\LogDao;
use Irail\Http\Requests\VehicleCompositionV2Request;
use Irail\Models\Dto\v2\VehicleCompositionV2Converter;
use Irail\Repositories\VehicleCompositionRepository;

class CompositionV2Controller extends BaseIrailController
{
    private HistoricCompositionDao $historicCompositionRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(HistoricCompositionDao $historicCompositionRepository)
    {
        $this->historicCompositionRepository = $historicCompositionRepository;
    }

    public function getVehicleComposition(VehicleCompositionV2Request $request): JsonResponse
    {
        $repo = app(VehicleCompositionRepository::class);
        $vehicleCompositionSearchResult = $repo->getComposition($request);
        $dto = VehiclecompositionV2Converter::convert($request, $vehicleCompositionSearchResult);
        $this->historicCompositionRepository->recordComposition($vehicleCompositionSearchResult);
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
        app(LogDao::class)->log('composition', $query, $request->getUserAgent());
    }
}
