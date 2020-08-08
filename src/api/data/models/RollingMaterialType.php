<?php

namespace Irail\api\data\models;

class RollingMaterialType
{
    /**
     * @var $parent_type string the parent type, such as I6, M5, HLE27, AM86 ...
     */
    public $parent_type;

    /**
     * @var $sub_type string the sub type, such as A, B, BD, BDx, C, ...
     */
    public $sub_type;
    /**
     * @var $orientation string The orientation of the vehicle, LEFT (default) or RIGHT.
     */
    public $orientation;
}
