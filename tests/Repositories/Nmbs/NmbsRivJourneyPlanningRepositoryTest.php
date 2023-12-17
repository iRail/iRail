<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
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
    function testGetLiveboard_departureBoardNormalCase_shouldParseDataCorrectly(): void
    {
        $stationsRepo = new StationsRepository();
        $rivRepo = Mockery::mock(NmbsRivRawDataRepository::class);
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo);
        $request = $this->createRequest('008892007', '008814308', TimeSelection::DEPARTURE, 'NL', Carbon::create(2023, 12, 17, 14, 14, 55));
        $rivRepo->shouldReceive('getRoutePlanningData')
            ->with($request)
            ->atLeast()
            ->once()
            ->andReturn(new CachedData(
                'sample-cache-key',
                file_get_contents(__DIR__ . '/NmbsRivJourneyPlanningRepositoryTest-008892007-008814308-1702818895.json')
            ));

        $response = $journeyPlanningRepo->getJourneyPlanning($request);

        self::assertEquals(6, count($response->getJourneys()));
        $journey = $response->getJourneys()[0];
        self::assertEquals(Carbon::create(2023, 12, 17, 14, 25, 0, 'Europe/Brussels'), $journey->getDeparture()->getScheduledDateTime());
        self::assertEquals('Gent-Sint-Pieters', $journey->getDeparture()->getStation()->getStationName());
        self::assertEquals(Carbon::create(2023, 12, 17, 15, 19, 0, 'Europe/Brussels'), $journey->getArrival()->getScheduledDateTime());
        self::assertEquals('008814308', $journey->getArrival()->getStation()->getId());
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