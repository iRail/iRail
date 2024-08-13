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
    private VehicleCompositionRepository $compositionRepository;
    private GtfsTripStartEndExtractor $gtfsTripStartEndExtractor;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        HistoricCompositionDao $historicCompositionRepository,
        VehicleCompositionRepository $compositionRepository,
        GtfsTripStartEndExtractor $gtfsTripStartEndExtractor
    ) {
        $this->historicCompositionRepository = $historicCompositionRepository;
        $this->compositionRepository = $compositionRepository;
        $this->gtfsTripStartEndExtractor = $gtfsTripStartEndExtractor;
    }

    public function getVehicleComposition(VehicleCompositionV1Request $request): Response
    {
        $journeyNumber = VehicleIdTools::extractTrainNumber($request->getVehicleId());
        $startDate = $this->gtfsTripStartEndExtractor->getStartDate($journeyNumber, $request->getDateTime());

        $vehicle = Vehicle::fromName($request->getVehicleId(), $startDate ?: Carbon::now());
        $vehicleCompositionSearchResult = $this->compositionRepository->getComposition($vehicle);
        $this->historicCompositionRepository->recordComposition($vehicleCompositionSearchResult);
        $dataRoot = VehicleCompositionV1Converter::convert($request, $vehicleCompositionSearchResult);

        $this->logRequest($request);

        return $this->outputV1($request, $dataRoot, 900); // This data rarely changes, cache for 15 minutes client side
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
