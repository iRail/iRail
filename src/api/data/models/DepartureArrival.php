<?php

namespace Irail\api\data\models;

class DepartureArrival
{
    /**
     * @var int Delay in seconds
     */
    public $delay;

    public $station;
    /**
     * @var int Departure/Arrival time as unix timestamp
     */
    public $time;

    public $vehicle;

    public $platform;

    public $canceled;
}
