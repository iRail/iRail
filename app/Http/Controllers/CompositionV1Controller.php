<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Database\LogDao;
use Irail\Http\Requests\VehicleCompositionV1Request;
use Irail\Models\Dto\v1\VehicleCompositionV1Converter;
use Irail\Repositories\VehicleCompositionRepository;

class CompositionV1Controller extends BaseIrailController
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

    public function getVehicleComposition(VehicleCompositionV1Request $request): Response
    {
        $repo = app(VehicleCompositionRepository::class);
        $vehicleCompositionSearchResult = $repo->getComposition($request);
        $dataRoot = VehicleCompositionV1Converter::convert($request, $vehicleCompositionSearchResult);

        $this->logRequest($request);

        return $this->outputV1($request, $dataRoot);
    }

    /**
     * @param VehicleCompositionV1Request $request
     * @return void
     */
    public function logRequest(VehicleCompositionV1Request $request): void
    {
        $query = [
            'id'            => $request->getVehicleId(),
            'language'      => $request->getLanguage(),
            'serialization' => $request->getResponseFormat(),
            'version'       => 1
        ];
        app(LogDao::class)->log('composition', $query, $request->getUserAgent());
    }

}
