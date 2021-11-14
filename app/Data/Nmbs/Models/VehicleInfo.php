<?php

namespace Irail\Data\Nmbs\Models;

use Irail\Data\Nmbs\Models\hafas\HafasVehicle;
use Irail\Data\Nmbs\Tools\VehicleIdTools;

class VehicleInfo
{
    public function __construct(HafasVehicle $hafasVehicle)
    {
        $this->{'@id'} = 'http://irail.be/vehicle/' . $hafasVehicle->name;
        $this->shortname = $hafasVehicle->name;
        $this->name = 'BE.NMBS.' . $hafasVehicle->name;
        $this->type = VehicleIdTools::extractTrainType($hafasVehicle->name);
        $this->number = VehicleIdTools::extractTrainNumber($hafasVehicle->name);
    }

    public $name;

    public $shortname;

    public $number;

    public $type;

    public $locationX = null;

    public $locationY = null;
}
