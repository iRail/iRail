<?php

namespace Tests\Util;

use Exception;
use Irail\Util\VehicleIdTools;
use Tests\TestCase;

class VehicleIdToolsTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_icTrain_shouldReturnCorrectTrainNumber()
    {
        self::assertTrainNumberIsParsedCorrectly('IC', '538');
        self::assertTrainNumberIsParsedCorrectly('IC', '3427');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_icTrain_shouldReturnCorrectTrainType()
    {
        self::assertTrainNumberIsParsedCorrectly('IC', '538');
        self::assertTrainNumberIsParsedCorrectly('IC', '3427');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_lTrain_shouldReturnCorrectTrainNumber()
    {
        self::assertTrainNumberIsParsedCorrectly('L', '855');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_lTrain_shouldReturnCorrectTrainType()
    {
        self::assertTrainNumberIsParsedCorrectly('L', '855');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_pTrain_shouldReturnCorrectTrainNumber()
    {
        self::assertTrainNumberIsParsedCorrectly('P', '7741');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_pTrain_shouldReturnCorrectTrainType()
    {
        self::assertTrainNumberIsParsedCorrectly('P', '7741');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_iceTrain_shouldReturnCorrectTrainNumber()
    {
        self::assertTrainNumberIsParsedCorrectly('ICE', '10');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_iceTrain_shouldReturnCorrectTrainType()
    {
        self::assertTrainNumberIsParsedCorrectly('ICE', '10');
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_sTrain_shouldReturnCorrectTrainNumber()
    {
        self::assertTrainNumberIsParsedCorrectly('S1', '1790');
        self::assertTrainNumberIsParsedCorrectly('S1', '1991');
        self::assertTrainNumberIsParsedCorrectly('S2', '3770');
        self::assertTrainNumberIsParsedCorrectly('S5', '3369');
        self::assertTrainNumberIsParsedCorrectly('S8', '6370');
        self::assertTrainNumberIsParsedCorrectly('S20', '3070');
        self::assertTrainNumberIsParsedCorrectly('S32', '2569');
        self::assertTrainNumberIsParsedCorrectly('S32', '400');
        self::assertTrainNumberIsParsedCorrectly('S32', '401');
        self::assertTrainNumberIsParsedCorrectly('S41', '5377');
        self::assertTrainNumberIsParsedCorrectly('S43', '5370');
        self::assertTrainNumberIsParsedCorrectly('S44', '5190');
        self::assertTrainNumberIsParsedCorrectly('S51', '790');
        self::assertTrainNumberIsParsedCorrectly('S51', '8085');
        self::assertTrainNumberIsParsedCorrectly('S52', '1870');
        self::assertTrainNumberIsParsedCorrectly('S61', '4590');
    }

    /**
     * Large test based on GTFS data, to ensure all known train numbers are handled correctly.
     * @throws Exception
     */
    public function testExtractTrainNumber_listOfAllKnownTrainNumbers_shouldReturnCorrectTrainNumber()
    {
        $data = file_get_contents(__DIR__ . '/../Fixtures/trainNumbers.txt');
        foreach (explode(PHP_EOL, $data) as $trainDesignation) {
            $trainDesignation = trim($trainDesignation);
            $type = explode(' ', $trainDesignation)[0];
            $number = explode(' ', $trainDesignation)[1];
            self::assertTrainNumberIsParsedCorrectly($type, $number);
        }
    }

    private static function assertTrainNumberIsParsedCorrectly(string $type, string $number): void
    {
        self::assertEquals($number, VehicleIdTools::extractTrainNumber($type . ' ' . $number), "Incorrect number while parsing $type $number");
        self::assertEquals($number, VehicleIdTools::extractTrainNumber($type . $number), "Incorrect number while parsing $type $number as '$type$number'");
        self::assertEquals($type, VehicleIdTools::extractTrainType($type . ' ' . $number), "Incorrect type while parsing $type $number");
        self::assertEquals($type, VehicleIdTools::extractTrainType($type . $number), "Incorrect type while parsing $type $number as '$type$number'");
    }

}