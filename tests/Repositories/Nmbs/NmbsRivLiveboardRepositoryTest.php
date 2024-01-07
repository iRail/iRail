<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsRivLiveboardRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Mockery;
use Tests\TestCase;

class NmbsRivLiveboardRepositoryTest extends TestCase
{
    function testGetLiveboard_departureBoardNormalCase_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $gtfsStartEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $liveboardRepo = new NmbsRivLiveboardRepository($stationsRepo, $gtfsStartEndExtractor, $rivRepo);

        $request = $this->createRequest('008892007', TimeSelection::DEPARTURE, 'NL', Carbon::create(2022, 12, 11, 20, 20));
        $rivRepo->shouldReceive('getLiveboardData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/NmbsRivLiveboardRepositoryTest_ghentDepartures.json')
            ));

        $response = $liveboardRepo->getLiveboard($request);

        self::assertEquals(20, count($response->getStops()));
    }

    function testGetLiveboard_departureBoardIncludingServiceTrain_shouldNotBeIncludedInResult(): void
    {

    }


    function testGetLiveboard_departureBoardMissingDestination_shouldGetDirectionFromGtfs(): void
    {
        // TODO: implement in code and test
    }


    function testGetLiveboard_departureBoardAndGtfsMissingDestination_shouldNotIncludeRowInResult(): void
    {
        // TODO: implement in code and test
    }

    function testGetLiveboard_canceledDeparture_shouldBeMarkedAsCanceled(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $gtfsStartEndExtractor = Mockery::mock(GtfsTripStartEndExtractor::class);
        $liveboardRepo = new NmbsRivLiveboardRepository($stationsRepo, $gtfsStartEndExtractor, $rivRepo);

        $request = $this->createRequest('008821006', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 1, 7, 14, 50));
        $rivRepo->shouldReceive('getLiveboardData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/NmbsRivLiveboardRepositoryTest_antwerpDepartures.json')
            ));

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
        return $mock;
    }
}