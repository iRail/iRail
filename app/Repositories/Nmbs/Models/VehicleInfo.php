<?php

namespace Irail\Repositories\Nmbs\Models;

use Irail\Repositories\Nmbs\Tools\VehicleIdTools;

class VehicleInfo
{
    public function __construct(string $type, int $number)
    {
        $this->{'@id'} = 'http://irail.be/vehicle/' . $type . $number;
        $this->shortname = $type . $number;
        $this->name = 'BE.NMBS.' . $type . $number;
        $this->type = $type;
        $this->number = $number;
    }

    public static function fromHafasVehicle(HafasVehicle $hafasVehicle)
    {
        return new VehicleInfo(
            VehicleIdTools::extractTrainType($hafasVehicle->name),
            VehicleIdTools::extractTrainNumber($hafasVehicle->name)
        );
    }

    public $name;

    public $shortname;

    public $number;

    public $type;

    public $locationX = 0;

    public $locationY = 0;
}
