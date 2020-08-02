<?php

namespace Irail\api\data\models;

class Platform
{
    public $name;

    public $normal;


    /**
     * Platform constructor.
     * @param $name
     * @param $normal
     */
    public function __construct($name = null, $normal = null)
    {
        $this->name = $name;

        $this->normal = $normal;
    }
}