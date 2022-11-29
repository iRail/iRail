<?php


namespace Irail\Repositories\Nmbs\Models\hafas;

use Irail\Repositories\Nmbs\Models\HafasDepartureArrival;
use Irail\Repositories\Nmbs\Models\VehicleInfo;
use stdClass;

class HafasConnectionLeg
{

    /**
     * @var HafasDepartureArrival
     */
    public $arrival;

    /**
     * @var HafasDepartureArrival
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
