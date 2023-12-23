<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Models\Dto\v2\DatedVehicleJourneyV2Converter;
use Irail\Repositories\Irail\HistoricCompositionRepository;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\VehicleCompositionRepository;
use Irail\Repositories\VehicleJourneyRepository;

class DatedVehicleJourneyV2Controller extends BaseIrailController
{
    private VehicleJourneyRepository $vehicleJourneyRepository;
    private VehicleCompositionRepository $vehicleCompositionRepository;
    private HistoricCompositionRepository $historicCompositionRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        VehicleJourneyRepository $vehicleJourneyRepository,
        VehicleCompositionRepository $vehicleCompositionRepository,
        HistoricCompositionRepository $historicCompositionRepository
    ) {
        //
        $this->vehicleJourneyRepository = $vehicleJourneyRepository;
        $this->vehicleCompositionRepository = $vehicleCompositionRepository;
        $this->historicCompositionRepository = $historicCompositionRepository;
    }

    public function getDatedVehicleJourney(DatedVehicleJourneyV2Request $request): JsonResponse
    {
        $vehicleJourneySearchResult = $this->vehicleJourneyRepository->getDatedVehicleJourney($request);

        $composition = $this->vehicleCompositionRepository->getComposition($vehicleJourneySearchResult->getVehicle(), $request->getDateTime());
        $compositionStatistics = $this->historicCompositionRepository->getHistoricCompositionStatistics(
            $vehicleJourneySearchResult->getVehicle()->getType(),
            $vehicleJourneySearchResult->getVehicle()->getNumber()
        );

        $dto = DatedVehicleJourneyV2Converter::convert($request, $vehicleJourneySearchResult,$composition, $compositionStatistics);
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
