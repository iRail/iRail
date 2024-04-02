<?php

namespace Tests\Repositories\Nmbs;

use Irail\Repositories\Nmbs\NmbsRivCompositionRepository;
use Tests\TestCase;

class NmbsTrainMapCompositionRepositoryTest extends TestCase
{

    public function testGetMaterialType_AM86()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_am86_s7_3479.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 0; $i < count($materialUnits); $i++) {
            $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals('AM86', $rollingMaterialType->getParentType());
            if ($i == 0) {
                $this->assertEquals('a', $rollingMaterialType->getSubType());
            }
            if ($i == 1) {
                $this->assertEquals('b', $rollingMaterialType->getSubType());
            }
        }
    }

    public function testGetMaterialType_AM08M()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_am08m_s6_1557.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 0; $i < count($materialUnits); $i++) {
            $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals('AM08M', $rollingMaterialType->getParentType());
            if ($i % 3 == 0) {
                $this->assertEquals('a', $rollingMaterialType->getSubType());
            }
            if ($i % 3 == 1) {
                $this->assertEquals('b', $rollingMaterialType->getSubType());
            }
            if ($i % 3 == 2) {
                $this->assertEquals('c', $rollingMaterialType->getSubType());
            }
        }
    }

    public function testGetMaterialType_AM80M()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_am80m_ic_3031.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 0; $i < count($materialUnits); $i++) {
            $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals('AM80M', $rollingMaterialType->getParentType());
            if ($i % 3 == 0) {
                $this->assertEquals('a', $rollingMaterialType->getSubType());
            }
            if ($i % 3 == 1) {
                $this->assertEquals('b', $rollingMaterialType->getSubType());
            }
            if ($i % 3 == 2) {
                $this->assertEquals('c', $rollingMaterialType->getSubType());
            }
        }
    }

    public function testGetMaterialType_M6()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[1], 1);

        $this->assertEquals('M6', $rollingMaterialType->getParentType());
        $this->assertEquals('BDUH', $rollingMaterialType->getSubType());
    }

    public function testGetMaterialType_M7()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 5; $i < 11; $i++) {
            $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals('M7', $rollingMaterialType->getParentType());
            if ($i <= 7) {
                $this->assertEquals('ABUH', $rollingMaterialType->getSubType());
            } else {
                $this->assertEquals('BUH', $rollingMaterialType->getSubType());
            }
        }
    }

    public function testGetMaterialType_I11()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;

        for ($i = 2; $i < 4; $i++) {
            $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[$i], $i);

            $this->assertEquals('I11', $rollingMaterialType->getParentType());
            $this->assertEquals('BUH', $rollingMaterialType->getSubType());
        }
    }

    public function testGetMaterialType_I10()
    {
        $jsonData = json_decode(file_get_contents(__DIR__ . '/../../Fixtures/composition_m6_i11_i10_m7_ic508.json'));
        $materialUnits = $jsonData[0]->materialUnits;
        $rollingMaterialType = NmbsRivCompositionRepository::getMaterialType($materialUnits[4], 4);
        $this->assertEquals('I10', $rollingMaterialType->getParentType());
        $this->assertEquals('BUH', $rollingMaterialType->getSubType());
    }
}