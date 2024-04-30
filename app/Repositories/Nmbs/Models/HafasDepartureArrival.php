<?php

namespace Irail\Repositories\Nmbs\Models;

class HafasDepartureArrival
{
    public $time;
    /**
     * @var Platform
     */
    public $platform;
    /**
     * @var int
     */
    public $isExtraStop;
    /**
     * @var HafasStation
     */
    public $station;
    /**
     * @var int
     */
    public $canceled;
    /**
     * @var int
     */
    public $delay;
}
