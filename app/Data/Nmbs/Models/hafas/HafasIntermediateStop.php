<?php


namespace Irail\Data\Nmbs\Models\hafas;

class HafasIntermediateStop
{

    /**
     * @var \Irail\Data\Nmbs\Models\Station
     */
    public $station;
    /**
     * @var int
     */
    public $scheduledArrivalTime;
    /**
     * @var bool
     */
    public $arrivalCanceled;
    /**
     * @var int
     */
    public $arrived;
    /**
     * @var int
     */
    public $scheduledDepartureTime;
    /**
     * @var int
     */
    public $arrivalDelay;
    /**
     * @var int
     */
    public $departureDelay;
    /**
     * @var bool
     */
    public $departureCanceled;
    /**
     * @var int
     */
    public $left;
    /**
     * @var int
     */
    public $isExtraStop;
    /**
     * @var string
     */
    public $departureConnection;
}
