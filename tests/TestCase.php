<?php

namespace Tests;

use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\CachedData;
use Irail\Models\Vehicle;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Irail\Repositories\Riv\RivClient;
use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        config(['app.env' => 'testing', 'app.debug' => false]);
        return $app;
    }

    protected function mockFixtureRoutePlanningResponse(JourneyPlanningRequest $expectedRequest, string $fixtureFileName): NmbsRivRawDataRepository
    {
        $rawDataRepoMock = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawDataRepoMock->shouldReceive('getRoutePlanningData')
            ->with($expectedRequest)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                RivClient::validateAndDecodeRivResponse(file_get_contents(__DIR__ . '/Fixtures/' . $fixtureFileName))
            ));
        return $rawDataRepoMock;
    }

    protected function mockFixtureLiveboardResponse(LiveboardRequest $expectedRequest, string $fixtureFileName): NmbsRivRawDataRepository
    {
        $rawDataRepoMock = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawDataRepoMock->shouldReceive('getLiveboardData')
            ->with($expectedRequest)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                RivClient::validateAndDecodeRivResponse(file_get_contents(__DIR__ . '/Fixtures/' . $fixtureFileName))
            ));
        return $rawDataRepoMock;
    }

    protected function mockFixtureVehicleJourneyResponse(VehicleJourneyRequest $expectedRequest, string $fixtureFileName): NmbsRivRawDataRepository
    {
        $rawDataRepoMock = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawDataRepoMock->shouldReceive('getVehicleJourneyData')
            ->with($expectedRequest)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                RivClient::validateAndDecodeRivResponse(file_get_contents(__DIR__ . '/Fixtures/' . $fixtureFileName))
            ));
        return $rawDataRepoMock;
    }

    protected function mockFixtureVehicleCompositionResponse(
        Vehicle $expectedVehicleQuery, JourneyWithOriginAndDestination $journeyStartEnd,
        string $fixtureFileName): NmbsRivRawDataRepository
    {
        $rawDataRepoMock = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawDataRepoMock->shouldReceive('getVehicleCompositionData')
            ->with($expectedVehicleQuery, $journeyStartEnd)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                RivClient::validateAndDecodeRivResponse(file_get_contents(__DIR__ . '/Fixtures/' . $fixtureFileName))
            ));
        return $rawDataRepoMock;
    }
}
