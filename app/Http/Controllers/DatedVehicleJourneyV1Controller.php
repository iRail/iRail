<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\DatedVehicleJourneyV1Request;
use Irail\Http\Requests\VehicleCompositionV1Request;
use Irail\Http\Requests\VehicleCompositionV2Request;
use Irail\Models\Dto\v1\DatedVehicleJourneyV1Converter;
use Irail\Models\Dto\v1\LiveboardV1Converter;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\VehicleJourneyRepository;

class DatedVehicleJourneyV1Controller extends BaseIrailController
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
        $this->logRequest($request);
        return $this->outputV1($request, $dataRoot);
    }

    /**
     * @param DatedVehicleJourneyV1Request $request
     * @return void
     */
    public function logRequest(DatedVehicleJourneyV1Request $request): void
    {
        $query = [
            'vehicle'  => $request->getVehicleId(),
            'language' => $request->getLanguage(),
            'serialization' => $request->getResponseFormat(),
            'version'  => 1
        ];
        app(LogRepository::class)->log('VehicleInformation', $query, $request->getUserAgent());
    }
}
