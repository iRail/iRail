<?php

namespace Tests\unit\api\data\NMBS;

use Exception;
use Irail\api\data\NMBS\ConnectionsDatasource;
use Irail\api\data\NMBS\StationsDatasource;
use Irail\api\requests\ConnectionsRequest;
use PHPUnit\Framework\TestCase;

class ConnectionsDatasourceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_CreateNmbsPayload_shouldCreateValidPayload()
    {
        $from = StationsDatasource::getStationFromName("Brussel-zuid", "NL");
        $to = StationsDatasource::getStationFromName("Brussel-noord", "NL");
        $lang = "NL";
        $time = "10:00";
        $date = "20181001";
        $timesel = 'depart';
        $ypeOfTransportCode = ConnectionsDatasource::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;

        $payload = ConnectionsDatasource::createNmbsPayload($from, $to, $lang, $time, $date, $timesel, $ypeOfTransportCode);

        // Should be valid json
        $payloadArray = json_decode($payload, true);

        self::assertArrayHasKey('auth', $payloadArray);
        self::assertArrayHasKey('client', $payloadArray);

        self::assertArrayHasKey('lang', $payloadArray);
        self::assertEquals($lang, $payloadArray['lang']);

        self::assertArrayHasKey('svcReqL', $payloadArray);
        self::assertArrayHasKey(0, $payloadArray['svcReqL']);
        self::assertEquals('TripSearch', $payloadArray['svcReqL'][0]['meth']);
        self::assertEquals(1, $this->count($payloadArray['svcReqL']));
        self::assertArrayHasKey('ver', $payloadArray);
    }

    public function test_regression434_connectionMissingStops_shouldBeParsedCorrectly()
    {
        $serverData = file_get_contents(__DIR__ . "/fixtures/connections-issue434.json");
        $connections = ConnectionsDatasource::parseConnectionsAPI(
            $serverData,
            "en",
            $this->createMock(ConnectionsRequest::class)
        );
        self::assertNotNull($connections);
        self::assertEquals(10, count($connections));
    }


    public function test_regression432_connectionMissingAlertsRemarks_shouldContainAlertsAndRemarks()
    {
        $serverData = file_get_contents(__DIR__ . "/fixtures/connections-issue432.json");
        $connections = ConnectionsDatasource::parseConnectionsAPI(
            $serverData,
            "en",
            $this->createMock(ConnectionsRequest::class)
        );
        self::assertNotNull($connections);
        self::assertEquals(7, count($connections));

        self::assertEquals(0, count($connections[0]->remark));
        self::assertEquals(1, count($connections[0]->alert));
        self::assertEquals(
            "Wearing a mask is mandatory at stations, on platforms and on trains.",
            $connections[0]->alert[0]->description
        );
        self::assertEquals(2, count($connections[1]->remark));
        self::assertEquals(1, count($connections[1]->alert));
        self::assertEquals(
            "Note: The connection results may be incomplete [460])",
            $connections[1]->remark[0]->description
        );
    }

    public function test_connectionsWithWalkingLeg_shouldParseAndPrintCorrectly()
    {
        $serverData = file_get_contents(__DIR__ . "/fixtures/connections-walking-leg.json");
        $connections = ConnectionsDatasource::parseConnectionsAPI(
            $serverData,
            "en",
            $this->createMock(ConnectionsRequest::class)
        );
        self::assertNotNull($connections);
        self::assertEquals(6, count($connections));

        self::assertEquals(1, $connections[2]->via[0]->departure->walking);
        self::assertEquals(0, $connections[2]->via[0]->departure->left);
        self::assertEquals(0, $connections[2]->via[0]->departure->delay);
        self::assertEquals(0, $connections[2]->via[0]->departure->canceled);
        self::assertEquals("WALK", $connections[2]->via[0]->departure->direction->name);
        self::assertEquals("WALK", $connections[2]->via[0]->departure->vehicle->name);
        self::assertEquals("http://irail.be/connections/8811130/20210326/WALK", $connections[2]->via[0]->departure->departureConnection);
        self::assertEquals("http://irail.be/connections/8833001/20210326/S23785", $connections[2]->departure->departureConnection);

        self::assertEquals(1, $connections[2]->via[1]->arrival->walking);
        self::assertEquals(0, $connections[2]->via[1]->arrival->arrived);
        self::assertEquals(0, $connections[2]->via[1]->arrival->delay);
        self::assertEquals(0, $connections[2]->via[1]->arrival->canceled);
        self::assertEquals("WALK", $connections[2]->via[1]->arrival->direction->name);
        self::assertEquals("WALK", $connections[2]->via[1]->arrival->vehicle->name);
    }
}
