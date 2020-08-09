<?php

namespace Irail\api\data\models;

use Irail\api\data\models\hafas\HafasVehicle;
use Irail\api\data\NMBS\tools\VehicleIdTools;

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
