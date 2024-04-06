<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Database\HistoricCompositionDao;
use Irail\Database\LogDao;
use Irail\Exceptions\CompositionUnavailableException;
use Irail\Http\Requests\JourneyPlanningV2RequestImpl;
use Irail\Http\Responses\v2\JourneyPlanningV2Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\Result\JourneyPlanningSearchResult;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\VehicleCompositionRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class JourneyPlanningV2Controller extends BaseIrailController
{
    private JourneyPlanningRepository $journeyPlanningRepository;
    private VehicleCompositionRepository $vehicleCompositionRepository;
    private HistoricCompositionDao $historicCompositionRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        JourneyPlanningRepository $journeyPlanningRepository,
        VehicleCompositionRepository $vehicleCompositionRepository,
        HistoricCompositionDao $historicCompositionRepository
    ) {
        $this->journeyPlanningRepository = $journeyPlanningRepository;
        $this->vehicleCompositionRepository = $vehicleCompositionRepository;
        $this->historicCompositionRepository = $historicCompositionRepository;
    }

    public function getJourneyPlanning(JourneyPlanningV2RequestImpl $request): JsonResponse
    {
        $journeyPlanningResult = $this->journeyPlanningRepository->getJourneyPlanning($request);
        $this->addCompositionData($journeyPlanningResult);
        $dto = JourneyPlanningV2Converter::convert($request, $journeyPlanningResult);
        $this->logRequest($request, $journeyPlanningResult);
        return $this->outputJson($request, $dto);
    }

    /**
     * @param JourneyPlanningV2RequestImpl $request
     * @param JourneyPlanningSearchResult  $result
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function logRequest(JourneyPlanningV2RequestImpl $request, JourneyPlanningSearchResult $result): void
    {
        $query = [
            'language'      => $request->getLanguage(),
            'departureStop' => $this->getStopInLogFormat($request->getOriginStationId(), $request->routeOrGet('from')),
            'arrivalStop'   => $this->getStopInLogFormat($request->getDestinationStationId(), $request->routeOrGet('to')),
            'version'       => 2
        ];
        $queryResult = [
            'journeyoptions' => array_map(fn($journey) => $this->getResultInLogformat($journey), $result->getJourneys())
        ];
        app(LogDao::class)->log(LogQueryType::JOURNEYPLANNING, $query, $request->getUserAgent(), $queryResult);
    }

    private function getStopInLogFormat(string $stationId, string $stationSearchValue): array
    {
        $stop = app(StationsRepository::class)->getStationById($stationId);
        return [
            '@id'       => $stop->getUri(),
            'longitude' => $stop->getLongitude(),
            'latitude'  => $stop->getLatitude(),
            'name'      => $stop->getStationName(),
            'query'     => $stationSearchValue,
        ];

    }

    private function getResultInLogformat(Journey $journey): array
    {
        return ['journeys' => array_map(fn($leg) => $this->getLegInLogFormat($leg), $journey->getLegs())];
    }

    private
    function getLegInLogFormat(
        JourneyLeg $leg
    ): array {
        return [
            'trip'          => $leg->getVehicle()->getId(),
            'departureStop' => $leg->getDeparture()->getStation()->getUri(),
            'arrivalStop'   => $leg->getDeparture()->getStation()->getUri()
        ];
    }

    /**
     * @param JourneyPlanningSearchResult $journeyPlanningResult
     * @return void
     */
    public function addCompositionData(JourneyPlanningSearchResult $journeyPlanningResult): void
    {
        foreach ($journeyPlanningResult->getJourneys() as $jny) {
            foreach ($jny->getLegs() as $leg) {
                try {
                    $compositionResult = $this->vehicleCompositionRepository->getComposition($leg->getVehicle());
                    $leg->setComposition($compositionResult);
                    $this->historicCompositionRepository->recordComposition($compositionResult);
                } catch (CompositionUnavailableException $e) {
                    // TODO: fallback with expected data
                }
                $leg->setHistoricCompositionStatistics($this->historicCompositionRepository->getHistoricCompositionStatistics(
                    $leg->getVehicle()->getType(), $leg->getVehicle()->getNumber()));
            }
        }
    }
}
