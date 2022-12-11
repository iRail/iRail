<?php

namespace Tests\Repositories\Nmbs;

use DateTime;
use Irail\Http\Requests\LiveboardRequestImpl;
use Irail\Http\Requests\TimeSelection;
use Irail\Http\Requests\VehicleJourneyRequestImpl;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Tests\TestCase;
use function Tests\Integration\Data\Nmbs\Repositories\str_contains;

class RawDataRepositoryIntegrationTest extends TestCase
{
    public function testGetFreshLiveboardData_brusselsSouth_shouldReturnValidJson()
    {
        $repo = new NmbsRivRawDataRepository(new StationsRepository());
        $liveboardData = (string)$repo->getLiveboardData(
            new LiveboardRequestImpl('008814001',
                TimeSelection::DEPARTURE,
                'nl',
                new DateTime()))->getValue();
        self::assertNotEmpty($liveboardData);
        self::assertTrue(strlen($liveboardData) > 100, 'Liveboard raw data is shorter than expected');
        self::assertTrue(str_contains($liveboardData, 'Brussel-Zuid'), 'Liveboard raw data should contain the station name');
    }

    public function testGetFreshVehicleJourneyData_IC1545_shouldReturnValidJson()
    {
        $repo = new NmbsRivRawDataRepository(new StationsRepository());
        $vehicleJourneyData = (string)$repo->getVehicleJourneyData(
            new VehicleJourneyRequestImpl('IC1545',
                null,
                new DateTime(),
                'en'))->getValue();
        self::assertNotEmpty($vehicleJourneyData);
        self::assertTrue(strlen($vehicleJourneyData) > 100, 'Vehicle journey raw data is shorter than expected');
        self::assertTrue(str_contains($vehicleJourneyData, '1545'), 'Vehicle journey raw data should contain the vehicle name');
    }
}