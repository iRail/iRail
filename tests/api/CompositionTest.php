<?php

require_once __DIR__ . '/../../api/data/NMBS/Composition.php';

class CompositionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testExtractTrainNumber_sTrain_shouldReturnCorrectTrainNumber()
    {
        self::test_s_train("S1", "1790");
        self::test_s_train("S1", "1991");
        self::test_s_train("S2", "3770");
        self::test_s_train("S5", "3369");
        self::test_s_train("S8", "6370");
        self::test_s_train("S20", "3070");
        self::test_s_train("S32", "2569");
        self::test_s_train("S43", "5370");
        self::test_s_train("S44", "5190");
        self::test_s_train("S51", "790");
        self::test_s_train("S52", "1870");
        self::test_s_train("S61", "4590");
    }

    private static function test_s_train(string $sLine, string $number): void
    {
        self::assertEquals($number, Composition::extractTrainNumber($sLine . ' ' . $number));
        self::assertEquals($number, Composition::extractTrainNumber($sLine  . $number));
    }
}
