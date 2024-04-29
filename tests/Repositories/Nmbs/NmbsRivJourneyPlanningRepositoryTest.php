<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Database\OccupancyDao;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
use Irail\Models\JourneyLegType;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsRivJourneyPlanningRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Mockery;
use Tests\TestCase;

class NmbsRivJourneyPlanningRepositoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    function testGetJourneyPlanning_normalCase_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);

        $request = $this->createRequest('008892007', '008814308', TimeSelection::DEPARTURE, 'NL', Carbon::create(2023, 12, 17, 14, 14, 55));
        $rivRepo->shouldReceive('getRoutePlanningData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/../../Fixtures/journeyPlanning/NmbsRivJourneyPlanning-008892007-008814308-1702818895.json')
            ));

        $response = $journeyPlanningRepo->getJourneyPlanning($request);

        self::assertEquals(6, count($response->getJourneys()));
        $journey = $response->getJourneys()[0];
        self::assertEquals(Carbon::create(2023, 12, 17, 14, 25, 0, 'Europe/Brussels'), $journey->getDeparture()->getScheduledDateTime());
        self::assertEquals('Gent-Sint-Pieters', $journey->getDeparture()->getStation()->getStationName());
        self::assertEquals(Carbon::create(2023, 12, 17, 15, 19, 0, 'Europe/Brussels'), $journey->getArrival()->getScheduledDateTime());
        self::assertEquals('008814308', $journey->getArrival()->getStation()->getId());
    }

    function testGetJourneyPlanning_walkingLeg_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);

        $request = $this->createRequest('008821147', '008822210', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 5, 28, 22, 00, 00));
        $rivRepo->shouldReceive('getRoutePlanningData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/../../Fixtures/journeyPlanning/NmbsRivJourneyPlanning_walkingLeg.json')
            ));

        $response = $journeyPlanningRepo->getJourneyPlanning($request);

        self::assertEquals(6, count($response->getJourneys()));
        $journey = $response->getJourneys()[0];

        self::assertNotNull($journey->getDeparture()->getVehicle());
        self::assertEquals(JourneyLegType::JOURNEY, $journey->getLegs()[0]->getLegType());
        self::assertNotNull($journey->getLegs()[0]->getVehicle());
        self::assertNotNull($journey->getLegs()[0]->getDeparture()->getVehicle());
        self::assertNotNull($journey->getLegs()[0]->getArrival()->getVehicle());

        self::assertNull($journey->getArrival()->getVehicle());
        self::assertEquals(JourneyLegType::WALKING, $journey->getLegs()[1]->getLegType());
        self::assertNull($journey->getLegs()[1]->getVehicle());
        self::assertNull($journey->getLegs()[1]->getDeparture()->getVehicle());
        self::assertNull($journey->getLegs()[1]->getArrival()->getVehicle());
    }

    function testGetJourneyPlanning_missingDepartureIntermediateStop_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);

        $request = $this->createRequest('008821402', '008812005', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 4, 29, 19, 34, 00));
        $rivRepo->shouldReceive('getRoutePlanningData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/../../Fixtures/journeyPlanning/NmbsRivJourneyPlanning_missingDeparture.json')
            ));

        $response = $journeyPlanningRepo->getJourneyPlanning($request);

        self::assertEquals(6, count($response->getJourneys()));
        $journey = $response->getJourneys()[0];
    }

    private function createRequest(
        string $origin,
        string $destination,
        TimeSelection $timeSelection,
        string $language,
        Carbon $dateTime
    ): JourneyPlanningRequest {
        $mock = Mockery::mock(JourneyPlanningRequest::class);
        $mock->shouldReceive('getOriginStationId')->andReturn($origin);
        $mock->shouldReceive('getDestinationStationId')->andReturn($destination);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getDepartureArrivalMode')->andReturn($timeSelection);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn("$origin|$timeSelection->value|$language|$dateTime");
        return $mock;
    }
}