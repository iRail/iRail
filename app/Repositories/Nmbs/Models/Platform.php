<?php

namespace Irail\Repositories\Nmbs\Models;

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
