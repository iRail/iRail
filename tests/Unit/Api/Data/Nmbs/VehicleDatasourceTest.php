<?php


namespace Tests\Unit\Api\Data\Nmbs;

use Carbon\Carbon;
use Exception;
use Irail\Data\Nmbs\Repositories\RawDataRepository;
use Irail\Data\Nmbs\Repositories\StationsRepository;
use Irail\Data\Nmbs\VehicleDatasource;
use Irail\Models\CachedData;
use Irail\Models\Requests\VehicleJourneyRequestImpl;
use PHPUnit\Framework\TestCase;

class VehicleDatasourceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testIc2137_canceledFromFifthStop_shouldHavePlatformDataForFifthStop()
    {
        $request = new VehicleJourneyRequestImpl('IC2137', null, Carbon::createFromDate(2021, 11, 14), 'en');
        $rawDataRepo = \Mockery::mock(RawDataRepository::class);
        $rawDataRepo->shouldReceive('getVehicleJourneyData')->withArgs([$request])->andReturn(
            new CachedData('dummy-cache-key', file_get_contents(__DIR__ . '/../../../../Fixtures/datedVehicleJourney-ic2137-partiallyCancelled.json'))
        );
        $stationsRepo = new StationsRepository();
        $vehicleRepo = new VehicleDatasource($stationsRepo, $rawDataRepo);
        $datedVehicleJourney = $vehicleRepo->getDatedVehicleJourney($request);
        self::assertNotNull($datedVehicleJourney);
        self::assertCount(14, $datedVehicleJourney->getStops());
        for ($i = 0; $i < 4; $i++) {
            self::assertNotNull($datedVehicleJourney->getDeparture($i)->getPlatform());
            self::assertNotEquals('?', $datedVehicleJourney->getDeparture($i)->getPlatform());
            self::assertFalse($datedVehicleJourney->getDeparture($i)->isCancelled());
            $i > 0 && self::assertFalse($datedVehicleJourney->getArrival($i)->isCancelled());
        }
        self::assertTrue($datedVehicleJourney->getArrival($i)->isPlatformInfoAvailable());
        self::assertEquals('2', $datedVehicleJourney->getArrival($i)->getPlatform()->getDesignation());
        self::assertFalse($datedVehicleJourney->getArrival($i)->isCancelled());
        self::assertTrue($datedVehicleJourney->getDeparture($i)->isCancelled());
        self::assertFalse($datedVehicleJourney->getDeparture($i)->isPlatformInfoAvailable());
        for ($i = 5; $i < 13; $i++) {
            self::assertFalse($datedVehicleJourney->getDeparture($i)->isPlatformInfoAvailable());
            self::assertFalse($datedVehicleJourney->getArrival($i)->isPlatformInfoAvailable());
            self::assertTrue($datedVehicleJourney->getDeparture($i)->isCancelled());
            self::assertTrue($datedVehicleJourney->getArrival($i)->isCancelled());
        }
        self::assertFalse($datedVehicleJourney->getArrival($i)->isPlatformInfoAvailable());
        self::assertTrue($datedVehicleJourney->getArrival($i)->isCancelled());
        self::assertNull($datedVehicleJourney->getDeparture($i));
    }

    /**
     * @throws Exception
     */
    public function testIc1545_journeyMatchAndJourneyFetch_shouldReturnCorrectPlatformResult()
    {
        $request = new VehicleJourneyRequestImpl('IC1545', null, Carbon::createFromDate(2021, 11, 14), 'en');
        $rawDataRepo = \Mockery::mock(RawDataRepository::class);
        $rawDataRepo->shouldReceive('getVehicleJourneyData')->withArgs([$request])->andReturn(
            new CachedData('dummy-cache-key', file_get_contents(__DIR__ . '/../../../../Fixtures/datedVehicleJourney-ic1545.json'))
        );

        $stationsRepo = new StationsRepository();
        $vehicleRepo = new VehicleDatasource($stationsRepo, $rawDataRepo);
        $datedVehicleJourney = $vehicleRepo->getDatedVehicleJourney($request);
        self::assertCount(11, $datedVehicleJourney->getStops());
        $platforms = [4, 1, 1, 8, 5, 6, 2, 12];
        foreach ($platforms as $stopIndex => $expectedPlatform) {
            self::assertNotNull($datedVehicleJourney->getDeparture($stopIndex)->getPlatform());
            self::assertEquals($expectedPlatform, $datedVehicleJourney->getDeparture($stopIndex)->getPlatform()->getDesignation());
            self::assertFalse($datedVehicleJourney->getDeparture($stopIndex)->isCancelled());
            if ($stopIndex > 0) {
                self::assertNotNull($datedVehicleJourney->getArrival($stopIndex)->getPlatform());
                self::assertEquals($expectedPlatform, $datedVehicleJourney->getArrival($stopIndex)->getPlatform()->getDesignation());
                self::assertFalse($datedVehicleJourney->getArrival($stopIndex)->isCancelled());
            } else {
                self::assertNull($datedVehicleJourney->getArrival($stopIndex));
            }
        }
    }

    public function testTrn17302_canceledFirstAndSecondStop_shouldHaveLeftFirstStationIfArrivedAtOtherStations()
    {
        $serverData = file_get_contents(__DIR__ . '/fixtures/trn17302first_stops_cancelled_should_show_left_correct.json');
        $stops = VehicleDataSourceProxy::getStops($serverData, 'en', null);
        self::assertNotNull($stops);
        self::assertEquals(6, count($stops));
        for ($i = 2; $i < count($stops); $i++) {
            self::assertEquals(1, $stops[$i]->arrived, "Stop index $i");
        }
        self::assertEquals('?', $stops[0]->platform->name);
        self::assertEquals('?', $stops[1]->platform->name);
        // This train was reported at the third stop, therefore, the other stops should report it as arrived/left
        // The second stop:
        self::assertEquals(1, $stops[1]->arrived);
        self::assertEquals(1, $stops[1]->left);
        // The first stop:
        self::assertEquals(1, $stops[0]->left);
    }

    /**
     * @throws Exception
     */
    public function testIc1545_trainOverMidnight_shouldHaveCorrectDepartureDates()
    {
        $serverData = file_get_contents(__DIR__ . '/fixtures/ic1545_trainOverMidnight.json');
        $stops = VehicleDataSourceProxy::getStops($serverData, 'en', null);
        self::assertNotNull($stops);

        $departureDateYmd = date('Ymd', $stops[8]->scheduledDepartureTime);
        self::assertEquals('20200809', $departureDateYmd);
        $arrivalDateYmd = date('Ymd', $stops[8]->scheduledArrivalTime);
        self::assertEquals('20200809', $arrivalDateYmd);

        $departureDateYmd = $date = date('Ymd', $stops[9]->scheduledDepartureTime);
        self::assertEquals('20200810', $departureDateYmd);
        $arrivalDateYmd = date('Ymd', $stops[9]->scheduledArrivalTime);
        self::assertEquals('20200810', $arrivalDateYmd);
    }
}

