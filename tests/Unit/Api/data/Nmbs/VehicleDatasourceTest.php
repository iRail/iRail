<?php


namespace Tests\unit\api\data\NMBS;

use Exception;
use Irail\Data\Nmbs\VehicleDatasource;
use PHPUnit\Framework\TestCase;

class VehicleDatasourceTest extends TestCase
{
    public function testIc2137_canceledFromFifthStop_shouldHavePlatformDataForFifthStop()
    {
        $serverData = file_get_contents(__DIR__ . "/fixtures/ic2137_partially_canceled_train.json");
        $stops = VehicleDataSourceProxy::getStops($serverData, "en", null);
        self::assertNotNull($stops);
        self::assertEquals(14, count($stops));
        for ($i = 0; $i < 4; $i++) {
            self::assertNotEquals("?", $stops[$i]->platform->name);
            self::assertEquals(0, $stops[$i]->arrivalCanceled);
            self::assertEquals(0, $stops[$i]->departureCanceled);
        }
        self::assertNotEquals("?", $stops[4]->platform->name);
        self::assertEquals("2", $stops[4]->platform->name);
        self::assertEquals(0, $stops[4]->arrivalCanceled);
        self::assertEquals(1, $stops[4]->departureCanceled);
        for ($i = 5; $i < 13; $i++) {
            self::assertEquals("?", $stops[$i]->platform->name);
            self::assertEquals(1, $stops[$i]->arrivalCanceled);
            self::assertEquals(1, $stops[$i]->departureCanceled);
        }
        self::assertEquals("?", $stops[13]->platform->name);
        self::assertEquals(1, $stops[13]->arrivalCanceled);
    }

    public function testTrn17302_canceledFirstAndSecondStop_shouldHaveLeftFirstStationIfArrivedAtOtherStations()
    {
        $serverData = file_get_contents(__DIR__ . "/fixtures/trn17302first_stops_cancelled_should_show_left_correct.json");
        $stops = VehicleDataSourceProxy::getStops($serverData, "en", null);
        self::assertNotNull($stops);
        self::assertEquals(6, count($stops));
        for ($i = 2; $i < count($stops); $i++) {
            self::assertEquals(1, $stops[$i]->arrived, "Stop index $i");
        }
        self::assertEquals("?", $stops[0]->platform->name);
        self::assertEquals("?", $stops[1]->platform->name);
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
        $serverData = file_get_contents(__DIR__ . "/fixtures/ic1545_trainOverMidnight.json");
        $stops = VehicleDataSourceProxy::getStops($serverData, "en", null);
        self::assertNotNull($stops);

        $departureDateYmd = date("Ymd", $stops[8]->scheduledDepartureTime);
        self::assertEquals("20200809", $departureDateYmd);
        $arrivalDateYmd = date("Ymd", $stops[8]->scheduledArrivalTime);
        self::assertEquals("20200809", $arrivalDateYmd);

        $departureDateYmd = $date = date("Ymd", $stops[9]->scheduledDepartureTime);
        self::assertEquals("20200810", $departureDateYmd);
        $arrivalDateYmd = date("Ymd", $stops[9]->scheduledArrivalTime);
        self::assertEquals("20200810", $arrivalDateYmd);
    }
}


/**
 * This class allows us to access protected methods from our testcase
 */
class VehicleDataSourceProxy extends VehicleDatasource
{
    public static function getStops(string $serverData, string $lang, $occupancyArr)
    {
        return parent::getStops($serverData, $lang, $occupancyArr);
    }
}
