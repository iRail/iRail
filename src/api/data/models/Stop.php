<?php

namespace Irail\api\data\models;

class Stop
{
    public $station;

    public $time;

    public $delay;

    public $platform;

    public $canceled;

    public $left;

    public $arrived;

    /**
     * @var int
     */
    public $departureDelay;

    /**
     * @var int Boolean 0/1 value
     */
    public $departureCanceled;

    /**
     * @var int|null
     */
    public $scheduledDepartureTime;

    /**
     * @var int|null
     */
    public $scheduledArrivalTime;

    /**
     * @var int
     */
    public $arrivalDelay;

    /**
     * @var int Boolean 0/1 value
     */
    public $arrivalCanceled;

    /**
     * @var int
     */
    public $isExtraStop;

    /**
     * @var string
     */
    public $departureConnection;

}
