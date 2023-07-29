<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Irail\Http\Requests\JourneyPlanningV2RequestImpl;
use Irail\Models\Dto\v2\JourneyPlanningV2Converter;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\Result\JourneyPlanningSearchResult;
use Irail\Repositories\Irail\LogRepository;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\JourneyPlanningRepository;

class JourneyPlanningV2Controller extends BaseIrailController
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

    public function getJourneyPlanning(JourneyPlanningV2RequestImpl $request): JsonResponse
    {
        $repo = app(JourneyPlanningRepository::class);
        $journeyPlanningResult = $repo->getJourneyPlanning($request);
        $dto = JourneyPlanningV2Converter::convert($request, $journeyPlanningResult);
        $this->logRequest($request, $journeyPlanningResult);
        return $this->outputJson($request, $dto);
    }

    /**
     * @param JourneyPlanningV2RequestImpl $request
     * @param JourneyPlanningSearchResult  $result
     * @return void
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
        app(LogRepository::class)->log('Connections', $query, $request->getUserAgent(), $queryResult);
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

    private function getLegInLogFormat(JourneyLeg $leg): array
    {
        return ['trip'          => $leg->getVehicle()->getId(),
                'departureStop' => $leg->getDeparture()->getStation()->getUri(),
                'arrivalStop'   => $leg->getDeparture()->getStation()->getUri()
        ];
    }
}
