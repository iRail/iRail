<?php

namespace Irail\api\data\models;

class TrainComposition
{
    /**
     * @var String internal source of this data, for example "Atlas".
     */
    public $source;


    /**
     * @var TrainCompositionUnit[] the units in this composition.
     */
    public $unit;
}
