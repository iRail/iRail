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
    public $hafasId;
    public $locationX;
    public $locationY;
    public $id;
}

class DepartureArrival
{
    public $delay;
    public $station;
    public $time;
    public $vehicle;
    public $platform;
    public $canceled;
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
    public $shortname;
}

class ViaDepartureArrival
{
    public $time;
    public $platform;
    public $isExtraStop;
}

class Stop
{
    public $station;
    public $time;
    public $delay;
    public $platform;
    public $canceled;
}

class Alert
{
    public $header;
    public $description;
}
