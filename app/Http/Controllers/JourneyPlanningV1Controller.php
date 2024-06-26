<?php

namespace Irail\Http\Controllers;

use Illuminate\Http\Response;
use Irail\Database\LogDao;
use Irail\Http\Requests\JourneyPlanningV1RequestImpl;
use Irail\Http\Requests\JourneyPlanningV2RequestImpl;
use Irail\Http\Responses\v1\JourneyPlanningV1Converter;
use Irail\Models\Dao\LogQueryType;
use Irail\Models\Journey;
use Irail\Models\JourneyLeg;
use Irail\Models\JourneyLegType;
use Irail\Models\Result\JourneyPlanningSearchResult;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\JourneyPlanningRepository;

class JourneyPlanningV1Controller extends BaseIrailController
{
    private LogDao $logDao;
    private JourneyPlanningRepository $journeyPlanningRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(JourneyPlanningRepository $journeyPlanningRepository, LogDao $logDao)
    {
        $this->journeyPlanningRepository = $journeyPlanningRepository;
        $this->logDao = $logDao;
    }

    public function getJourneyPlanning(JourneyPlanningV1RequestImpl $request): Response
    {
        $journeyPlanningResult = $this->journeyPlanningRepository->getJourneyPlanning($request);
        $dataRoot = JourneyPlanningV1Converter::convert($request, $journeyPlanningResult);
        $this->logRequest($request, $journeyPlanningResult);
        return $this->outputV1($request, $dataRoot, 60);
    }

    /**
     * @param JourneyPlanningV2RequestImpl $request
     * @param JourneyPlanningSearchResult  $result
     * @return void
     */
    public function logRequest(JourneyPlanningV1RequestImpl $request, JourneyPlanningSearchResult $result): void
    {
        $query = [
            'language'      => $request->getLanguage(),
            'departureStop' => $this->getStopInLogFormat($request->getOriginStationId(), $request->getOriginStationId()),
            'arrivalStop'   => $this->getStopInLogFormat($request->getDestinationStationId(), $request->getDestinationStationId()),
            'version'       => 2
        ];
        $queryResult = [
            'journeyoptions' => array_map(fn ($journey) => $this->getResultInLogformat($journey), $result->getJourneys())
        ];
        $this->logDao->log(LogQueryType::JOURNEYPLANNING, $query, $request->getUserAgent(), $queryResult);
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
        return ['journeys' => array_map(fn ($leg) => $this->getLegInLogFormat($leg), $journey->getLegs())];
    }

    private function getLegInLogFormat(JourneyLeg $leg): array
    {
        return [
            'trip' => $leg->getLegType() == JourneyLegType::JOURNEY ? $leg->getVehicle()->getId() : 'Walk',
                'departureStop' => $leg->getDeparture()->getStation()->getUri(),
                'arrivalStop'   => $leg->getDeparture()->getStation()->getUri()
        ];
    }
}
