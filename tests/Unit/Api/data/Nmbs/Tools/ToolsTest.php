<?php

namespace Tests\unit\api\data\NMBS\tools;

use Exception;
use Irail\Data\Nmbs\Tools\Tools;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testTransformTime_timeUnder24Hours_shouldHaveCorrectDate()
    {
        $timestamp = Tools::transformTime("012345", "20201015");
        $date = date("Ymd", $timestamp);
        $time = date("His", $timestamp);
        self::assertEquals("20201015", $date);
        self::assertEquals("012345", $time);
    }

    /**
     * @throws Exception
     */
    public function testTransformTime_timeOver24Hours_shouldHaveCorrectDate()
    {
        $timestamp = Tools::transformTime("01012345", "20201015");
        $date = date("Ymd", $timestamp);
        $time = date("His", $timestamp);
        self::assertEquals("20201016", $date);
        self::assertEquals("012345", $time);
    }
}
