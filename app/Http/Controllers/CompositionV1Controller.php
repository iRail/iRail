<?php

namespace Irail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Irail\Database\LogDao;
use Irail\Http\Requests\VehicleCompositionV1Request;
use Irail\Http\Responses\v1\VehicleCompositionV1Converter;
use Irail\Models\Vehicle;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Nmbs\Tools\VehicleIdTools;
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
        $tripStartEndExtractor = app(GtfsTripStartEndExtractor::class);
        $journeyNumber = VehicleIdTools::extractTrainNumber($request->getVehicleId());
        $startDate = $tripStartEndExtractor->getStartDate($journeyNumber, $request->getDateTime());

        $vehicle = Vehicle::fromName($request->getVehicleId(), $startDate ?: Carbon::now());
        $repo = app(VehicleCompositionRepository::class);
        $vehicleCompositionSearchResult = $repo->getComposition($vehicle);
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
