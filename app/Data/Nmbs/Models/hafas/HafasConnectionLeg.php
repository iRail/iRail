<?php


namespace Irail\Data\Nmbs\Models\hafas;

use Irail\Data\Nmbs\Models\VehicleInfo;
use Irail\Data\Nmbs\Models\ViaDepartureArrival;
use stdClass;

class HafasConnectionLeg
{

    /**
     * @var ViaDepartureArrival
     */
    public $arrival;

    /**
     * @var ViaDepartureArrival
     */
    public $departure;

    /**
     * @var int
     */
    public $duration;
    /**
     * @var int
     */
    public $left;
    /**
     * @var int
     */
    public $arrived;
    /**
     * @var bool
     */
    public $isPartiallyCancelled;
    /**
     * @var HafasIntermediateStop[]
     */
    public $stops;

    /**
     * @var VehicleInfo
     */
    public $vehicle;
    /**
     * @var int
     */
    public $walking;
    /**
     * @var stdClass
     */
    public $direction;
    /**
     * @var array
     */
    public $alerts = [];
}
