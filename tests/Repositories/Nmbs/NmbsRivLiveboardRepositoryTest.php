<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Http\Requests\LiveboardRequestImplIrail;
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

        $request = new LiveboardRequestImplIrail('008892007', TimeSelection::DEPARTURE, 'NL', Carbon::createMidnightDate(2022, 12, 11));
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

    }


    function testGetLiveboard_departureBoardAndGtfsMissingDestination_shouldNotIncludeRowInResult(): void
    {

    }
}