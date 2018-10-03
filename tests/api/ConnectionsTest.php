<?php

require_once __DIR__ . '/../../api/data/NMBS/connections.php';
require_once __DIR__ . '/../../api/data/NMBS/stations.php';

class ConnectionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testCreateNmbsPayload()
    {
        $from = stations::getStationFromName("Brussel-zuid", "NL");
        $to = stations::getStationFromName("Brussel-noord", "NL");
        $lang = "NL";
        $time = "10:00";
        $date = "20181001";
        $timesel = 'depart';
        $ypeOfTransportCode = connections::TYPE_TRANSPORT_BITCODE_NO_INTERNATIONAL_TRAINS;

        $payload = connections::createNmbsPayload($from, $to, $lang, $time, $date, $timesel, $ypeOfTransportCode);

        // Should be valid json
        $payloadArray = json_decode($payload, true);

        self::assertArrayHasKey('auth', $payloadArray);
        self::assertArrayHasKey('client', $payloadArray);

        self::assertArrayHasKey('lang', $payloadArray);
        self::assertEquals($lang, $payloadArray['lang']);

        self::assertArrayHasKey('svcReqL', $payloadArray);
        self::assertArrayHasKey(0, $payloadArray['svcReqL']);
        self::assertEquals('TripSearch', $payloadArray['svcReqL'][0]['meth']);
        self::assertEquals(1,$this->count($payloadArray['svcReqL']));
        self::assertArrayHasKey('ver', $payloadArray);
    }


}