<?php

namespace Tests\Integration\Data\Nmbs\Repositories;

use DateTime;
use Irail\Data\Nmbs\Repositories\RawDataRepository;
use Irail\Data\Nmbs\Repositories\StationsRepository;
use Irail\Models\DepartureArrivalMode;
use Irail\Models\Requests\LiveboardRequestImpl;
use Irail\Models\Requests\VehicleJourneyRequestImpl;
use Tests\TestCase;

class RawDataRepositoryTest extends TestCase
{
    public function testGetFreshLiveboardData_brusselsSouth_shouldReturnValidJson()
    {
        $repo = new RawDataRepository(new StationsRepository());
        $liveboardData = (string)$repo->getLiveboardData(
            new LiveboardRequestImpl('008814001',
                DepartureArrivalMode::MODE_DEPARTURE,
                "nl",
                new DateTime()))->getValue();
        self::assertNotEmpty($liveboardData);
        self::assertTrue(strlen($liveboardData) > 100, "Liveboard raw data is shorter than expected");
        self::assertTrue(str_contains($liveboardData, "Brussel-Zuid"), "Liveboard raw data should contain the station name");
    }

    public function testGetFreshVehicleJourneyData_IC1545_shouldReturnValidJson()
    {
        $repo = new RawDataRepository(new StationsRepository());
        $vehicleJourneyData = (string)$repo->getVehicleJourneyData(
            new VehicleJourneyRequestImpl("IC1545",
                null,
                new DateTime(),
                'en'))->getValue();
        self::assertNotEmpty($vehicleJourneyData);
        self::assertTrue(strlen($vehicleJourneyData) > 100, "Vehicle journey raw data is shorter than expected");
        self::assertTrue(str_contains($vehicleJourneyData, "1545"), "Vehicle journey raw data should contain the vehicle name");
    }
}