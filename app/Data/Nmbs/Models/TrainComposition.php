<?php

namespace Irail\Data\Nmbs\Models;

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
