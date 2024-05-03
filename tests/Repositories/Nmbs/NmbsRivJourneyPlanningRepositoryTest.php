<?php

namespace Tests\Repositories\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Database\OccupancyDao;
use Irail\Http\Requests\JourneyPlanningRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Http\Requests\TypeOfTransportFilter;
use Irail\Models\JourneyLegType;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;
use Irail\Proxy\CurlHttpResponse;
use Irail\Proxy\CurlProxy;
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
    public function testGetJourneyPlanning_normalCase_shouldParseDataCorrectly(): void
    {
        $request = $this->createRequest('008892007', '008814308', TimeSelection::DEPARTURE, 'NL', Carbon::create(2023, 12, 17, 14, 14, 55));
        $rivRepo = $this->mockFixtureRoutePlanningResponse($request, 'journeyPlanning/NmbsRivJourneyPlanning-008892007-008814308-1702818895.json');
        $journeyPlanningRepo = $this->createJourneyPlanningRepo($rivRepo);

        $response = $journeyPlanningRepo->getJourneyPlanning($request);

        self::assertEquals(6, count($response->getJourneys()));
        $journey = $response->getJourneys()[0];
        self::assertEquals(Carbon::create(2023, 12, 17, 14, 25, 0, 'Europe/Brussels'), $journey->getDeparture()->getScheduledDateTime());
        self::assertEquals('Gent-Sint-Pieters', $journey->getDeparture()->getStation()->getStationName());
        self::assertEquals(Carbon::create(2023, 12, 17, 15, 19, 0, 'Europe/Brussels'), $journey->getArrival()->getScheduledDateTime());
        self::assertEquals('008814308', $journey->getArrival()->getStation()->getId());
    }

    public function testGetJourneyPlanning_walkingLeg_shouldParseDataCorrectly(): void
    {
        $request = $this->createRequest('008821147', '008822210', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 5, 28, 22, 00, 00));
        $rivRepo = $this->mockFixtureRoutePlanningResponse($request, 'journeyPlanning/NmbsRivJourneyPlanning_walkingLeg.json');
        $journeyPlanningRepo = $this->createJourneyPlanningRepo($rivRepo);

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

    public function testGetJourneyPlanning_missingDepartureIntermediateStop_shouldParseDataCorrectly(): void
    {
        $request = $this->createRequest('008821402', '008812005', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 4, 29, 19, 34, 00));
        $rivRepo = $this->mockFixtureRoutePlanningResponse($request, 'journeyPlanning/NmbsRivJourneyPlanning_missingDeparture.json');
        $journeyPlanningRepo = $this->createJourneyPlanningRepo($rivRepo);

        $response = $journeyPlanningRepo->getJourneyPlanning($request);

        self::assertEquals(6, count($response->getJourneys()));
    }

    public function testGetJourneyPlanning_noResult_shouldReturn404(): void
    {
        // This test is actually more of an integration test, as the exception needs to be handled *somewhere* but error handling is centralized closer to the actual HTTP request
        $stationsRepo = new StationsRepository();
        $curlProxy = Mockery::mock(CurlProxy::class);
        $curlProxy->shouldReceive('get')->andReturn(
            new CurlHttpResponse(Carbon::now(), 'POST', 'http://test', '', 200,
                file_get_contents(__DIR__ . '/../../Fixtures/journeyPlanning/NmbsRivJourneyPlanning_noResult.json'), 0)
        );
        $rivRepo = new NmbsRivRawDataRepository($stationsRepo, $curlProxy);
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);

        $request = $this->createRequest('008821402', '008812005', TimeSelection::DEPARTURE, 'NL', Carbon::create(2024, 4, 29, 19, 34, 00));

        try {
            $journeyPlanningRepo->getJourneyPlanning($request);
            self::fail('Should throw an exception');
        } catch (Exception $e) {
            self::assertEquals(404, $e->getCode());
        }
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
        $mock->shouldReceive('getTypesOfTransport')->zeroOrMoreTimes()->andReturn(TypeOfTransportFilter::ALL);
        $mock->shouldReceive('getTimeSelection')->zeroOrMoreTimes()->andReturn(TimeSelection::DEPARTURE);
        $mock->shouldReceive('getCacheId')->andReturn("$origin|$timeSelection->value|$language|$dateTime");
        return $mock;
    }

    /**
     * @param NmbsRivRawDataRepository $rivRepo
     * @return NmbsRivJourneyPlanningRepository
     */
    public function createJourneyPlanningRepo(NmbsRivRawDataRepository $rivRepo): NmbsRivJourneyPlanningRepository
    {
        $stationsRepo = new StationsRepository();
        $occupancyDao = Mockery::mock(OccupancyDao::class);
        $occupancyDao->shouldReceive('getOccupancy')->andReturn(new OccupancyInfo(OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN));
        $journeyPlanningRepo = new NmbsRivJourneyPlanningRepository($stationsRepo, $rivRepo, $occupancyDao);
        return $journeyPlanningRepo;
    }
}
