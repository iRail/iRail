<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\VehicleCompositionV1Request;
use Irail\Models\Dto\v1\VehicleCompositionV1Converter;
use Irail\Repositories\VehicleCompositionRepository;

class CompositionV1Controller extends IrailController
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
        return $this->outputV1($request, $dataRoot);
    }

}
