<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Concurrency;
use Irail\Database\HistoricCompositionDao;
use Irail\Database\LogDao;
use Irail\Exceptions\CompositionUnavailableException;
use Irail\Http\Requests\DatedVehicleJourneyV2Request;
use Irail\Http\Responses\v2\DatedVehicleJourneyV2Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Vehicle;
use Irail\Repositories\VehicleCompositionRepository;
use Irail\Repositories\VehicleJourneyRepository;
use Irail\Util\VehicleIdTools;

class DatedVehicleJourneyV2Controller extends BaseIrailController
{
    private VehicleJourneyRepository $vehicleJourneyRepository;
    private VehicleCompositionRepository $vehicleCompositionRepository;
    private HistoricCompositionDao $historicCompositionRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        VehicleJourneyRepository $vehicleJourneyRepository,
        VehicleCompositionRepository $vehicleCompositionRepository,
        HistoricCompositionDao $historicCompositionRepository
    ) {
        //
        $this->vehicleJourneyRepository = $vehicleJourneyRepository;
        $this->vehicleCompositionRepository = $vehicleCompositionRepository;
        $this->historicCompositionRepository = $historicCompositionRepository;
    }

    public function getDatedVehicleJourney(DatedVehicleJourneyV2Request $request): JsonResponse
    {
        // TODO: concurrency::run needs the illuminate/concurrency package, which has not been split yet from laravel 11.x
        [$vehicleJourneySearchResult, $composition, $statistics] = Concurrency::run([
            function () use ($request) {
                return $this->vehicleJourneyRepository->getDatedVehicleJourney($request);
            },
            function () use ($request) {
                try {
                    return $this->getVehicleComposition($request);
                } catch (CompositionUnavailableException $exception) {
                    return null;
                }
            },
            function () use ($request) {
                $trainType = VehicleIdTools::extractTrainType($request->getVehicleId());
                if (empty($trainType)){
                    // TODO: look up train type based on GTFS
                    return [];
                }
                return $this->historicCompositionRepository->getHistoricCompositionStatistics(
                    $trainType,
                    VehicleIdTools::extractTrainNumber($request->getVehicleId())
                );
            },
        ]);

        $compositionSegments = $composition ? $composition->getSegments() : []; // $composition Can be null when unavailable
        $dto = DatedVehicleJourneyV2Converter::convert($request, $vehicleJourneySearchResult, $compositionSegments, $statistics);
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
        app(LogDao::class)->log(LogQueryType::DATEDVEHICLEJOURNEY, $query, $request->getUserAgent());
    }

    /**
     * @param DatedVehicleJourneyV2Request $request
     * @return VehicleCompositionSearchResult
     */
    public function getVehicleComposition(DatedVehicleJourneyV2Request $request): VehicleCompositionSearchResult
    {
        // The type may not be determined successfully, but only the number is needed anyway
        $vehicle = Vehicle::fromName($request->getVehicleId(), $request->getDateTime());
        $composition = $this->vehicleCompositionRepository->getComposition($vehicle);
        // Store this in the database, in case it's new.
        $this->historicCompositionRepository->recordComposition($composition);
        return $composition;
    }
}
