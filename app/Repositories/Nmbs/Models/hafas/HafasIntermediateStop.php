<?php


namespace Irail\Repositories\Nmbs\Models\hafas;

use Irail\api\data\models\Platform;
use Irail\Repositories\Nmbs\Models\Station;

class HafasIntermediateStop
{

    /**
     * @var Station
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

    public Platform $platform;
}
