<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Database\OccupancyDao;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsRivLiveboardRepository;
use Mockery;
use Tests\TestCase;

class NmbsRivLiveboardRepositoryTest extends TestCase
{
    public function testGetLiveboard_departureBoardNormalCase_shouldParseDataCorrectly(): void
    {
        $request = $this->createRequest('008892007', TimeSelection::DEPARTURE, 'NL', Carbon::create(2022, 12, 11, 20, 20));
        $rivRepo = $this->mockFixtureLiveboardResponse($request, 'departures/ghentDepartures.json');

        $stationsRepo = new StationsRepository();
        $gtfsStartEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $gtfsStartEndExtractor->expects('getStartDate')->times(20)->andReturn(Carbon::create(2022, 12, 11));
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $liveboardRepo = new NmbsRivLiveboardRepository($stationsRepo, $gtfsStartEndExtractor, $rivRepo, $occupancyDao);
        $response = $liveboardRepo->getLiveboard($request);

        self::assertEquals(20, count($response->getStops()));
    }

    public function testGetLiveboard_departureBoardIncludingServiceTrain_shouldNotBeIncludedInResult(): void
    {
    }


    public function testGetLiveboard_departureBoardMissingDestination_shouldGetDirectionFromGtfs(): void
    {
        // TODO: implement in code and test
    }


    public function testGetLiveboard_departureBoardAndGtfsMissingDestination_shouldNotIncludeRowInResult(): void
    {
        // TODO: implement in code and test
    }

    public function testGetLiveboard_canceledDeparture_shouldBeMarkedAsCanceled(): void
    {
        $request = $this->createRequest('008821006', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 1, 7, 14, 50));
        $rivRepo = $this->mockFixtureLiveboardResponse($request, 'departures/antwerpDepartures.json');

        $stationsRepo = new StationsRepository();
        $gtfsStartEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $gtfsStartEndExtractor->expects('getStartDate')->times(14)->andReturn(Carbon::create(2022, 12, 11));
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $liveboardRepo = new NmbsRivLiveboardRepository($stationsRepo, $gtfsStartEndExtractor, $rivRepo, $occupancyDao);

        $response = $liveboardRepo->getLiveboard($request);

        self::assertEquals(14, count($response->getStops()));
        self::assertTrue($response->getStops()[3]->isCancelled());
        self::assertEquals('?', $response->getStops()[3]->getPlatform()->getDesignation());
    }

    private function createRequest(string $station, TimeSelection $timeSelection, string $language, Carbon $dateTime): LiveboardRequest
    {
        $mock = Mockery::mock(LiveboardRequest::class);
        $mock->shouldReceive('getStationId')->andReturn($station);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getDepartureArrivalMode')->andReturn($timeSelection);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn("$station|$timeSelection->value|$language|$dateTime");
        return $mock;
    }
}
