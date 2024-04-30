<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Proxy\CurlProxy;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Mockery;
use Tests\TestCase;

class RawDataRepositoryIntegrationTest extends TestCase
{
    public function testGetFreshLiveboardData_brusselsSouth_shouldReturnValidJson()
    {
        $repo = new NmbsRivRawDataRepository(new StationsRepository(), new CurlProxy());
        $liveboardData = (string)$repo->getLiveboardData(
            $this->createLiveboardRequest(
                '008814001',
                TimeSelection::DEPARTURE,
                'nl',
                Carbon::now()
            )
        )->getValue();
        self::assertNotEmpty($liveboardData);
        self::assertTrue(strlen($liveboardData) > 100, 'Liveboard raw data is shorter than expected');
        self::assertTrue(str_contains($liveboardData, 'DestinationNl'), 'Liveboard raw data should contain the expected destination field');
    }

    public function testGetFreshVehicleJourneyData_IC1545_shouldReturnValidJson()
    {
        $repo = new NmbsRivRawDataRepository(new StationsRepository(), new CurlProxy());
        $vehicleJourneyData = (string)$repo->getVehicleJourneyData(
            $this->createDatedVehicleJourneyRequest(
                'IC1545',
                Carbon::now(),
                'en'
            )
        )->getValue();
        self::assertNotEmpty($vehicleJourneyData);
        self::assertTrue(strlen($vehicleJourneyData) > 100, 'Vehicle journey raw data is shorter than expected');
        self::assertTrue(str_contains($vehicleJourneyData, '1545'), 'Vehicle journey raw data should contain the vehicle name');
    }

    private function createLiveboardRequest(string $station, TimeSelection $timeSelection, string $language, Carbon $dateTime): LiveboardRequest
    {
        $mock = Mockery::mock(LiveboardRequest::class);
        $mock->shouldReceive('getStationId')->andReturn($station);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getDepartureArrivalMode')->andReturn($timeSelection);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn('RawDataRepositoryIntegrationTest|1');
        return $mock;
    }

    private function createDatedVehicleJourneyRequest(string $vehicle, Carbon $dateTime, string $language): VehicleJourneyRequest
    {
        $mock = Mockery::mock(VehicleJourneyRequest::class);
        $mock->shouldReceive('getVehicleId')->andReturn($vehicle);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn('RawDataRepositoryIntegrationTest|2');
        return $mock;
    }
}
