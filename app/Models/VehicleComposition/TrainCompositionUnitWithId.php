<?php

namespace Irail\Models\VehicleComposition;

/**
 * A train composition unit, including a UIC code and some additional fields.
 */
class TrainCompositionUnitWithId extends TrainCompositionUnit
{
    /**
     * @var int The UIC code of this vehicle
     */
    private int $uicCode;

    /**
     * @var integer The number for this car or motor-unit, visible to the traveler.
     */
    private int $materialNumber;

    /**
     * @var string The material subtype name, as specified by the railway company. Examples are AM80_c or M6BUH.
     */
    private string $materialSubTypeName;

    public function getUicCode(): int
    {
        return $this->uicCode;
    }

    public function setUicCode(int $uicCode): TrainCompositionUnitWithId
    {
        $this->uicCode = $uicCode;
        return $this;
    }


    public function getMaterialNumber(): int
    {
        return $this->materialNumber;
    }

    public function setMaterialNumber(int $materialNumber): TrainCompositionUnitWithId
    {
        $this->materialNumber = $materialNumber;
        return $this;
    }

    public function getMaterialSubTypeName(): string
    {
        return $this->materialSubTypeName;
    }

    public function setMaterialSubTypeName(string $materialSubTypeName): TrainCompositionUnitWithId
    {
        $this->materialSubTypeName = $materialSubTypeName;
        return $this;
    }
}
