<?php

namespace Tests\unit\api\data\NMBS;
use Exception;
use Irail\api\data\NMBS\Connections;
use Irail\api\data\NMBS\Stations;
use PHPUnit\Framework\TestCase;

class ConnectionsTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCreateNmbsPayload()
    {
        $from = Stations::getStationFromName("Brussel-zuid", "NL");
        $to = Stations::getStationFromName("Brussel-noord", "NL");
        $lang = "NL";
        $time = "10:00";
        $date = "20181001";
        $timesel = 'depart';
        $ypeOfTransportCode = Connections::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;

        $payload = Connections::createNmbsPayload($from, $to, $lang, $time, $date, $timesel, $ypeOfTransportCode);

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
}
