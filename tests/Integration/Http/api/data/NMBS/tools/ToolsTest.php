<?php

namespace api\data\NMBS\tools;

use Irail\api\data\NMBS\tools\Tools;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase
{

    public function testTransformDurationHHMMSS()
    {
        self::assertEquals(1800, Tools::transformDurationHHMMSS("003000"));
        self::assertEquals(3600, Tools::transformDurationHHMMSS("010000"));
        self::assertEquals(5400, Tools::transformDurationHHMMSS("013000"));
        self::assertEquals(86400 + 7200 + 180 + 4, Tools::transformDurationHHMMSS("01020304"));
    }

    public function testTransformDuration()
    {
        self::assertEquals(1800, Tools::transformDuration("PT1800S"));
        self::assertEquals(1800, Tools::transformDuration("PT30M"));
        self::assertEquals(3600, Tools::transformDuration("PT60M"));
        self::assertEquals(3600, Tools::transformDuration("PT1H"));
        self::assertEquals(5400, Tools::transformDuration("PT1H30M"));
        self::assertEquals(5400, Tools::transformDuration("PT90M"));
        self::assertEquals(6600, Tools::transformDuration("PT1H50M"));
        self::assertEquals(86400 + 7200 + 180 + 4, Tools::transformDuration("P1DT2H3M4S"));
    }

    public function testCalculateSecondsHHMMSS()
    {
        self::assertEquals(-60, Tools::calculateSecondsHHMMSS("01:01:00", "2022-10-30", "01:02:00", "2022-10-30"));
        self::assertEquals(0, Tools::calculateSecondsHHMMSS("01:03:00", "2022-10-30", "01:03:00", "2022-10-30"));
        self::assertEquals(124, Tools::calculateSecondsHHMMSS("01:02:03", "2022-10-30", "00:59:59", "2022-10-30"));
        self::assertEquals(86400, Tools::calculateSecondsHHMMSS("00:01:02:03", "2022-10-30", "00:01:02:03", "2022-10-29"));
        self::assertEquals(86400, Tools::calculateSecondsHHMMSS("01:02:03", "2022-10-02", "01:02:03", "2022-10-01"));
        self::assertEquals(86400, Tools::calculateSecondsHHMMSS("01:02:03", "2022-10-01", "01:02:03", "2022-09-30"));
        self::assertEquals(86400 + 124, Tools::calculateSecondsHHMMSS("01:02:03", "2022-11-01", "00:59:59", "2022-10-31"));
    }

    public function testTransformTime()
    {
        self::assertEquals(1667084580, Tools::transformTime("01:03:00", "2022-10-30"));
        self::assertEquals(1667084580 - 86400, Tools::transformTime("01:03:00", "2022-10-29"));
        self::assertEquals(1667084523, Tools::transformTime("01:02:03", "2022-10-30"));
        self::assertEquals(1667084399, Tools::transformTime("00:59:59", "2022-10-30"));
    }
}
