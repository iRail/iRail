<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Models\Dto\v2\DatedVehicleJourneyV2Converter;
use Irail\Models\Vehicle;
use Irail\Repositories\Irail\HistoricCompositionRepository;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\VehicleCompositionRepository;
use Irail\Repositories\VehicleJourneyRepository;
use Spatie\Async\Pool;

class DatedVehicleJourneyV2Controller extends BaseIrailController
{
    private VehicleJourneyRepository $vehicleJourneyRepository;
    private VehicleCompositionRepository $vehicleCompositionRepository;
    private HistoricCompositionRepository $historicCompositionRepository;
    private Pool $pool;

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
        $this->pool = new Pool();
    }

    public function getDatedVehicleJourney(DatedVehicleJourneyV2Request $request): JsonResponse
    {

        $statistics = null;
        $compositionTask = $this->pool
            ->add(function () use ($request) {
                // The type may not be determined successfully, but only the number is needed anyway
                $vehicle = Vehicle::fromName($request->getVehicleId(), $request->getDateTime());
                $composition = $this->vehicleCompositionRepository->getComposition($vehicle);
                // Store this in the database, in case it's new.
                $this->historicCompositionRepository->recordComposition($composition);
            });

        $vehicleJourneySearchResult = $this->vehicleJourneyRepository->getDatedVehicleJourney($request);
        $statistics = $this->historicCompositionRepository->getHistoricCompositionStatistics(
            $vehicleJourneySearchResult->getVehicle()->getType(),
            $vehicleJourneySearchResult->getVehicle()->getNumber()
        );
        $this->pool->wait();
        $composition = $compositionTask->getOutput();
        $dto = DatedVehicleJourneyV2Converter::convert($request, $vehicleJourneySearchResult, $composition->getSegments(), $statistics);
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
