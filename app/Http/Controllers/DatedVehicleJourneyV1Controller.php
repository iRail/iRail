<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Database\LogDao;
use Irail\Http\Requests\DatedVehicleJourneyV1Request;
use Irail\Http\Responses\v1\DatedVehicleJourneyV1Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Repositories\VehicleJourneyRepository;

class DatedVehicleJourneyV1Controller extends BaseIrailController
{
    private VehicleJourneyRepository $vehicleJourneyRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(VehicleJourneyRepository $vehicleJourneyRepository)
    {
        $this->vehicleJourneyRepository = $vehicleJourneyRepository;
    }

    public function getVehicleById(DatedVehicleJourneyV1Request $request): Response
    {
        $vehicleJourneySearchResult = $this->vehicleJourneyRepository->getDatedVehicleJourney($request);
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
        app(LogDao::class)->log(LogQueryType::DATEDVEHICLEJOURNEY, $query, $request->getUserAgent());
    }
}
