<?php

namespace Irail\Models\VehicleComposition;

class RollingMaterialType
{
    /**
     * @var string the parent type, such as I6, M5, HLE27, AM86 ...
     */
    private string $parentType;

    /**
     * @var string the subtype, such as A, B, BD, BDx, C, ...
     */
    private string $subType;

    /**
     * @var RollingMaterialOrientation The orientation of the vehicle, LEFT (default) or RIGHT.
     */
    private RollingMaterialOrientation $orientation = RollingMaterialOrientation::LEFT;

    /**
     * @param string $parentType
     * @param string $subType
     */
    public function __construct(string $parentType, string $subType)
    {
        $this->parentType = $parentType;
        $this->subType = $subType;
    }

    /**
     * @return string
     */
    public function getParentType(): string
    {
        return $this->parentType;
    }

    /**
     * @return string
     */
    public function getSubType(): string
    {
        return $this->subType;
    }

    /**
     * @return RollingMaterialOrientation
     */
    public function getOrientation(): RollingMaterialOrientation
    {
        return $this->orientation;
    }

    public function setOrientation(RollingMaterialOrientation $value): RollingMaterialType
    {
        $this->orientation = $value;
        return $this;
    }
}
