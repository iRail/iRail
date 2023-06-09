<?php

namespace Tests\unit\api\data\NMBS;

use Irail\api\data\NMBS\CompositionDataSource;
use PHPUnit\Framework\TestCase;

class CompositionDataSourceTest extends TestCase
{
    public function testGetMaterialType_AM86()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_am86_s7_3479.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 0; $i < count($materialUnits); $i++) {
            $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals("AM86", $rollingMaterialType->parent_type);
            if ($i == 0) {
                $this->assertEquals("a", $rollingMaterialType->sub_type);
            }
            if ($i == 1) {
                $this->assertEquals("b", $rollingMaterialType->sub_type);
            }
        }
    }

    public function testGetMaterialType_AM08M()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_am08m_s6_1557.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 0; $i < count($materialUnits); $i++) {
            $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals("AM08M", $rollingMaterialType->parent_type);
            if ($i % 3 == 0) {
                $this->assertEquals("a", $rollingMaterialType->sub_type);
            }
            if ($i % 3 == 1) {
                $this->assertEquals("b", $rollingMaterialType->sub_type);
            }
            if ($i % 3 == 2) {
                $this->assertEquals("c", $rollingMaterialType->sub_type);
            }
        }
    }

    public function testGetMaterialType_AM80M()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_am80m_ic_3031.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 0; $i < count($materialUnits); $i++) {
            $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals("AM80M", $rollingMaterialType->parent_type);
            if ($i % 3 == 0) {
                $this->assertEquals("a", $rollingMaterialType->sub_type);
            }
            if ($i % 3 == 1) {
                $this->assertEquals("b", $rollingMaterialType->sub_type);
            }
            if ($i % 3 == 2) {
                $this->assertEquals("c", $rollingMaterialType->sub_type);
            }
        }
    }

    public function testGetMaterialType_M6()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[1], 1);

        $this->assertEquals("M6", $rollingMaterialType->parent_type);
        $this->assertEquals("BDUH", $rollingMaterialType->sub_type);
    }

    public function testGetMaterialType_M7()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 5; $i < 11; $i++) {
            $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals("M7", $rollingMaterialType->parent_type);
            if ($i <= 7) {
                $this->assertEquals("ABUH", $rollingMaterialType->sub_type);
            } else {
                $this->assertEquals("BUH", $rollingMaterialType->sub_type);
            }
        }
    }

    public function testGetMaterialType_I11()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 2; $i < 4; $i++) {
            $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals("I11", $rollingMaterialType->parent_type);
            $this->assertEquals("BUH", $rollingMaterialType->sub_type);
        }
    }

    public function testGetMaterialType_I10()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;
        $rollingMaterialType = CompositionDataSource::getMaterialType($materialUnits[4], 4);
        $this->assertEquals("I10", $rollingMaterialType->parent_type);
        $this->assertEquals("BUH", $rollingMaterialType->sub_type);
    }
}
