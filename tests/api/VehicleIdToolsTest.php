<?php

require_once __DIR__ . '/../../api/data/NMBS/Composition.php';

class VehicleIdToolsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_icTrain_shouldReturnCorrectTrainNumber()
    {
        self::verify_extract_train_number("IC", "538");
        self::verify_extract_train_number("IC", "3427");
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_icTrain_shouldReturnCorrectTrainType()
    {
        self::verify_extract_train_number("IC", "538");
        self::verify_extract_train_number("IC", "3427");
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_lTrain_shouldReturnCorrectTrainNumber()
    {
        self::verify_extract_train_number("L", "855");
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_lTrain_shouldReturnCorrectTrainType()
    {
        self::verify_extract_train_number("L", "855");
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_pTrain_shouldReturnCorrectTrainNumber()
    {
        self::verify_extract_train_number("P", "7741");
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_pTrain_shouldReturnCorrectTrainType()
    {
        self::verify_extract_train_number("P", "7741");
    }



    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_sTrain_shouldReturnCorrectTrainNumber()
    {
        self::verify_extract_train_number("S1", "1790");
        self::verify_extract_train_number("S1", "1991");
        self::verify_extract_train_number("S2", "3770");
        self::verify_extract_train_number("S5", "3369");
        self::verify_extract_train_number("S8", "6370");
        self::verify_extract_train_number("S20", "3070");
        self::verify_extract_train_number("S32", "2569");
        self::verify_extract_train_number("S43", "5370");
        self::verify_extract_train_number("S44", "5190");
        self::verify_extract_train_number("S51", "790");
        self::verify_extract_train_number("S52", "1870");
        self::verify_extract_train_number("S61", "4590");
    }

    /**
     * @throws Exception
     */
    public function testExtractTrainType_sTrain_shouldReturnCorrectTrainType()
    {
        self::verify_extract_train_type("S1", "1790");
        self::verify_extract_train_type("S1", "1991");
        self::verify_extract_train_type("S2", "3770");
        self::verify_extract_train_type("S5", "3369");
        self::verify_extract_train_type("S8", "6370");
        self::verify_extract_train_type("S20", "3070");
        self::verify_extract_train_type("S32", "2569");
        self::verify_extract_train_type("S43", "5370");
        self::verify_extract_train_type("S44", "5190");
        self::verify_extract_train_type("S51", "790");
        self::verify_extract_train_type("S52", "1870");
        self::verify_extract_train_type("S61", "4590");
    }

    private static function verify_extract_train_number(string $type, string $number): void
    {
        self::assertEquals($number, VehicleIdTools::extractTrainNumber($type . ' ' . $number));
        self::assertEquals($number, VehicleIdTools::extractTrainNumber($type . $number));
    }

    private static function verify_extract_train_type(string $type, string $number): void
    {
        self::assertEquals($type, VehicleIdTools::extractTrainType($type . ' ' . $number));
        self::assertEquals($type, VehicleIdTools::extractTrainType($type . $number));
    }
}
