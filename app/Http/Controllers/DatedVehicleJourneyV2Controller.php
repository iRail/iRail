<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Http\Requests\VehicleCompositionV2Request;
use Irail\Models\Dto\v2\DatedVehicleJourneyV2Converter;
use Irail\Repositories\Irail\LogRepository;
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
        $this->logRequest($request);
        return $this->outputJson($request, $dto);
    }

    /**
     * @param DatedVehicleJourneyV2Request $request
     * @return void
     */
    public function logRequest(DatedVehicleJourneyV2Request $request): void
    {
        $query = [
            'vehicle'  => $request->getVehicleId(),
            'language' => $request->getLanguage(),
            'version'  => 2
        ];
        app(LogRepository::class)->log('VehicleInformation', $query, $request->getUserAgent());
    }
}
