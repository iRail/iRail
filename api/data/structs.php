<?php
/** Copyright (C) 2011 by iRail vzw/asbl 
 * This is the Connection class. It contains data.
 *
 * @author pieterc
 */

  //class Liveboard {
  //  public $station;
     //public $departure;
     //public $arrival;
  // }

class Connection
{
    public $departure;
    public $arrival;
//     public $via; // not compulsory
     public $duration;
}

class Station
{
    private $hafasid;

    public function setHID($id)
    {
        $this->hafasid = $id;
    }

    public function getHID()
    {
        return $this->hafasid;
    }
}

class DepartureArrival
{
    public $delay;
    public $station;
    public $time;
    public $vehicle;
    public $platform;
}

class Platform
{
    public $name;
    public $normal;
}

class Via
{
    public $arrival;
    public $departure;
    public $timeBetween;
    public $station;
    public $vehicle;
}

class Vehicle
{
    public $locationX;
    public $locationY;
    public $name;
}

class ViaDepartureArrival
{
    public $time;
    public $platform;
}

//class VehicleInformation{
//     public $vehicle;
//     public $stop;
//}

class Stop
{
    public $station;
    public $time;
    public $delay;
	public $platform;
}
