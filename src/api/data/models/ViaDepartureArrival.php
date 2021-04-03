<?php

namespace Irail\api\data\models;

class ViaDepartureArrival
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
     * @var Station
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
