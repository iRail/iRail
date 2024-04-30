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

    public function testGetDatedVehicleJourney_ic729_shouldParseCancelStatusCorrectly()
    {
        $rawRivDataRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawRivDataRepo->expects('getVehicleJourneyData')->andReturn(
            new CachedData('test', file_get_contents(__DIR__ . '/NmbsRivVehicleJourneyRepository_ic729.json'), 1)
        );
        $repo = new NmbsRivVehicleJourneyRepository(new StationsRepository(), $rawRivDataRepo);
        $request = $this->createRequest('729', 'en', Carbon::create(2024, 4, 25));
        $result = $repo->getDatedVehicleJourney($request);

        $stops = $result->getStops();
        $this->assertEquals('008896008', $stops[7]->getStation()->getId()); // Kortrijk

        // Cancelled from the start
        $this->assertTrue($stops[0]->getDeparture()->isCancelled());
        $this->assertTrue($stops[6]->getDeparture()->isCancelled());
        $this->assertTrue($stops[6]->getArrival()->isCancelled());

        // Starts running at Kortrijk
        $this->assertTrue($stops[7]->getArrival()->isCancelled());
        $this->assertFalse($stops[7]->getDeparture()->isCancelled());

        $this->assertFalse($stops[8]->getDeparture()->isCancelled());
        $this->assertFalse($stops[8]->getArrival()->isCancelled());

        // Only arriving at Sint-Niklaas, not departing.
        $this->assertFalse($stops[12]->getArrival()->isCancelled());
        $this->assertTrue($stops[12]->getDeparture()->isCancelled());

        // Cancelled until the end
        $this->assertTrue($stops[13]->getArrival()->isCancelled());
        $this->assertTrue($stops[13]->getDeparture()->isCancelled());
        $this->assertTrue($stops[14]->getArrival()->isCancelled());
    }

    public function testGetDatedVehicleJourney_ic1866_shouldCalculateMissingDirection()
    {
        $rawRivDataRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $rawRivDataRepo->expects('getVehicleJourneyData')->andReturn(
            new CachedData('test', file_get_contents(__DIR__ . '/NmbsRivVehicleJourneyRepository_ic1866.json'), 1)
        );
        $repo = new NmbsRivVehicleJourneyRepository(new StationsRepository(), $rawRivDataRepo);
        $request = $this->createRequest('1866', 'en', Carbon::create(2024, 4, 27));
        $result = $repo->getDatedVehicleJourney($request);
        $this->assertEquals('Grammont / Geraardsbergen', $result->getVehicle()->getDirection()->getName());
        $this->assertEquals('008895505', $result->getVehicle()->getDirection()->getStation()->getId());
        $this->assertEquals('Geraardsbergen', $result->getVehicle()->getDirection()->getStation()->getStationName());
        $this->assertEquals(Carbon::create(2024, 4, 27), $result->getVehicle()->getJourneyStartDate());
        $this->assertCount(10, $result->getStops());
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
