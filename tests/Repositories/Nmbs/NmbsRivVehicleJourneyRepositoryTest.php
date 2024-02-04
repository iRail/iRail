<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\CachedData;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsRivVehicleJourneyRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Mockery;
use Tests\TestCase;

class NmbsRivVehicleJourneyRepositoryTest extends TestCase
{
    public function testGetDatedVehicleJourney_p7281_shouldParseDataCorrectly()
    {
        $rawRivDataRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawRivDataRepo->expects('getVehicleJourneyData')->andReturn(
            new CachedData('test', file_get_contents(__DIR__ . '/NmbsRivVehicleJourneyRepository_p7281.json'), 1)
        );
        $repo = new NmbsRivVehicleJourneyRepository(new StationsRepository(), $rawRivDataRepo);
        $request = $this->createRequest('7281', 'en', Carbon::create(2024, 1, 20));
        $result = $repo->getDatedVehicleJourney($request);
        $this->assertEquals('Lierre / Lier & Anvers-Central / Antwerpen-Centraal', $result->getVehicle()->getDirection()->getName());
        $this->assertEquals('008821006', $result->getVehicle()->getDirection()->getStation()->getId());
        $this->assertEquals('Antwerpen-Centraal', $result->getVehicle()->getDirection()->getStation()->getStationName());
        $this->assertEquals(Carbon::create(2024, 1, 30), $result->getVehicle()->getJourneyStartDate());
        $this->assertCount(13, $result->getStops());
    }

    private function createRequest(string $journeyId, string $language, Carbon $dateTime): VehicleJourneyRequest
    {
        $mock = Mockery::mock(VehicleJourneyRequest::class);
        $mock->shouldReceive('getVehicleId')->andReturn($journeyId);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn("$journeyId|$language|$dateTime");
        return $mock;
    }
}