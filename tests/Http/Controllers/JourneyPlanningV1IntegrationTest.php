<?php

namespace Tests\Http\Controllers;

use Carbon\Carbon;
use Irail\Database\LogDao;
use Irail\Database\OccupancyDao;
use Irail\Http\Controllers\JourneyPlanningV1Controller;
use Irail\Http\Requests\JourneyPlanningV1RequestImpl;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\NmbsRivJourneyPlanningRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Mockery;
use Tests\TestCase;

class JourneyPlanningV1IntegrationTest extends TestCase
{
    public function testGetJourneyPlanning_walkingLeg_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);

        $logDao = Mockery::mock(LogDao::class);
        $logDao->expects('log')->times(1)->andReturns();
        $controller = new JourneyPlanningV1Controller($journeyPlanningRepo, $logDao);

        $request = $this->createRequest('008821147', '008822210', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 5, 28, 22, 00, 00));
        $rivRepo->shouldReceive('getRoutePlanningData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/../../Fixtures/journeyPlanning/NmbsRivJourneyPlanning_walkingLeg.json')
            ));

        $response = $controller->getJourneyPlanning($request);
        self::assertEquals(200, $response->getStatusCode()); // Just ensure the response could be parsed and converted.
    }

    public function testGetJourneyPlanning_missingDepartureIntermediateStop_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);

        $logDao = Mockery::mock(LogDao::class);
        $logDao->expects('log')->times(1)->andReturns();
        $controller = new JourneyPlanningV1Controller($journeyPlanningRepo, $logDao);

        $request = $this->createRequest('008821402', '008812005', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 4, 29, 19, 34, 00));
        $rivRepo->shouldReceive('getRoutePlanningData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/../../Fixtures/journeyPlanning/NmbsRivJourneyPlanning_missingDeparture.json')
            ));

        $response = $controller->getJourneyPlanning($request);
        self::assertEquals(200, $response->getStatusCode()); // Just ensure the response could be parsed and converted.
    }

    private function createRequest(
        string $origin,
        string $destination,
        TimeSelection $timeSelection,
        string $language,
        Carbon $dateTime
    ): JourneyPlanningV1RequestImpl {
        $mock = Mockery::mock(JourneyPlanningV1RequestImpl::class);
        $mock->shouldReceive('getOriginStationId')->andReturn($origin);
        $mock->shouldReceive('getDestinationStationId')->andReturn($destination);
        $mock->shouldReceive('getDateTime')->andReturn($dateTime);
        $mock->shouldReceive('getDepartureArrivalMode')->andReturn($timeSelection);
        $mock->shouldReceive('getLanguage')->andReturn($language);
        $mock->shouldReceive('getCacheId')->andReturn("$origin|$timeSelection->value|$language|$dateTime");
        $mock->shouldReceive('getUserAgent')->andReturn('Sample user agent');
        $mock->shouldReceive('getResponseFormat')->andReturn('json');
        return $mock;
    }
}
