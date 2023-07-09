<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\DatedVehicleJourneyV1Request;
use Irail\Models\Dto\v1\DatedVehicleJourneyV1Converter;
use Irail\Models\Dto\v1\LiveboardV1Converter;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\VehicleJourneyRepository;

class DatedVehicleJourneyV1Controller extends IrailController
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

    public function getVehicleById(DatedVehicleJourneyV1Request $request): Response
    {
        $repo = app(VehicleJourneyRepository::class);
        $vehicleJourneySearchResult = $repo->getDatedVehicleJourney($request);
        $dataRoot = DatedVehicleJourneyV1Converter::convert($request, $vehicleJourneySearchResult);
        return $this->outputV1($request, $dataRoot);
    }

}
