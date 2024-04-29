<?php

namespace Irail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Irail\Database\HistoricCompositionDao;
use Irail\Database\LogDao;
use Irail\Http\Requests\VehicleCompositionV1Request;
use Irail\Http\Responses\v1\VehicleCompositionV1Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Models\Vehicle;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\VehicleCompositionRepository;
use Irail\Util\VehicleIdTools;

class CompositionV1Controller extends BaseIrailController
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

    public function getVehicleComposition(VehicleCompositionV1Request $request): Response
    {
        $tripStartEndExtractor = app(GtfsTripStartEndExtractor::class);
        $journeyNumber = VehicleIdTools::extractTrainNumber($request->getVehicleId());
        $startDate = $tripStartEndExtractor->getStartDate($journeyNumber, $request->getDateTime());

        $vehicle = Vehicle::fromName($request->getVehicleId(), $startDate ?: Carbon::now());
        $repo = app(VehicleCompositionRepository::class);
        $vehicleCompositionSearchResult = $repo->getComposition($vehicle);
        $this->historicCompositionRepository->recordComposition($vehicleCompositionSearchResult);
        $dataRoot = VehicleCompositionV1Converter::convert($request, $vehicleCompositionSearchResult);

        $this->logRequest($request);

        return $this->outputV1($request, $dataRoot, 180); // This data rarely changes, cache for 3 minutes
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
        app(LogDao::class)->log(LogQueryType::VEHICLECOMPOSITION, $query, $request->getUserAgent());
    }

}
