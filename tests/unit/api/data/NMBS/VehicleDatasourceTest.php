<?php


namespace Tests\unit\api\data\NMBS;

use Irail\api\data\NMBS\VehicleDatasource;
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
