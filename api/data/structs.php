<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * This file contains classes used in API responses.
 *
 * @author pieterc
 */

//class Liveboard {
//  public $station;

//public $departure;

//public $arrival;

// }

class Disturbance
{
    public $title;
    public $description;
    // public $attachment; // Not compulsory, commented to ensure null values don't cause issues in the printer
    public $link; // Not compulsory
    public $type;
    public $timestamp;
}

class Connection
{
    public $departure;

    public $arrival;

    public $via;
    // not compulsory
    public $duration;
}

class Station
{
    public $hafasId;

    public $locationX;

    public $locationY;

    public $id;

    public $name;
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


    /**
     * Platform constructor.
     * @param $name
     * @param $normal
     */
    public function __construct($name = null, $normal = null)
    {
        $this->name = $name;

        $this->normal = $normal;
    }
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

class TrainCompositionResult
{
    /**
     * @var $segment TrainCompositionInSegment[] A list of all segments with their own composition for this train ride.
     */
    public $segment;
}


class TrainCompositionInSegment
{
    public $origin;

    public $destination;


    /**
     * @var $composition TrainComposition.
     */
    public $composition;
}

class TrainComposition
{
    /**
     * @var String internal source of this data, for example "Atlas".
     */
    public $source;


    /**
     * @var TrainCompositionUnit[] the units in this composition.
     */
    public $unit;
}

class TrainCompositionUnit
{

    //-- Computed fields

    /**
     * @var $materialType RollingMaterialType
     */
    public $materialType;

    //-- Guaranteed fields

    /**
     * @var $hasToilets boolean Indicates if this unit has toilets.
     */
    public $hasToilets;

    /**
     * @var $hasTables boolean Indicates if this unit has tables.
     */
    public $hasTables;

    /**
     * @var $hasSecondClassOutlets boolean Indicates if this unit has 230v power outlets in 2nd class.
     */
    public $hasSecondClassOutlets;

    /**
     * @var $hasFirstClassOutlets boolean Indicates if this unit has 230v power outlets in 1st class.
     */
    public $hasFirstClassOutlets;

    /**
     * @var $hasHeating boolean Indicates if this unit has heating.
     */
    public $hasHeating;

    /**
     * @var $hasAirco boolean Indicates if this unit has air-conditioning.
     */
    public $hasAirco;

    /**
     * @var $materialNumber integer The number for this car or motor-unit, visible to the traveler.
     */
    public $materialNumber;


    /**
     * @var $tractionType string
     */
    public $tractionType;

    /**
     * @var $canPassToNextUnit boolean Whether or not people can pass to the next unit, backwards in the driving direction.
     */
    public $canPassToNextUnit;

    /**
     * @var $seatsCoupeSecondClass integer The number of standing positions in second class.
     */
    public $standingPlacesSecondClass;

    /**
     * @var $seatsCoupeSecondClass integer The number of standing positions in first class.
     */
    public $standingPlacesFirstClass;


    /**
     * @var $seatsCoupeSecondClass integer The number of seats in second class coupes.
     */
    public $seatsCoupeSecondClass;


    /**
     * @var $seatsCoupeFirstClass integer The number of seats in first class coupes.
     */
    public $seatsCoupeFirstClass;

    /**
     * @var $seatsSecondClass integer The number of seats in second class.
     */
    public $seatsSecondClass;

    /**
     * @var $seatsFirstClass integer The number of seats in first class.
     */
    public $seatsFirstClass;

    /**
     * @var $materialSubTypeName integer The length of this unit in metric meters.
     */
    public $lengthInMeter;

    /**
     * @var $hasSemiAutomaticInteriorDoors boolean Indicates if this unit has semi-automatic or manual interior doors
     */
    public $hasSemiAutomaticInteriorDoors;

    /**
     * @var $hasLuggageSection boolean Indicates if this unit has a luggage section.
     */
    public $hasLuggageSection;


    /**
     * @var $materialSubTypeName string The material subtype name, as specified by the railway company. Examples are AM80_c or M6BUH.
     */
    public $materialSubTypeName;

    /**
     * @var $tractionPosition int The traction group in which this carriage belongs. For example, two trains coupled together have 2 tractionpositions.
     */
    public $tractionPosition;

    /**
     * @var $hasPrmSection boolean Zhether or not this carriage has a section for persons with a reduced mobility.
     */
    public $hasPrmSection;

    /**
     * @var $hasPriorityPlaces boolean Whether or not this carriage has priority places (places with priority for those who are old, pregnant, ...)
     */
    public $hasPriorityPlaces;
    //-- Other types can be included dynamically but aren't guaranteed to be present
}

class RollingMaterialType
{
    /**
     * @var $parent_type string the parent type, such as I6, M5, HLE27, AM86 ...
     */
    public $parent_type;

    /**
     * @var $sub_type string the sub type, such as A, B, BD, BDx, C, ...
     */
    public $sub_type;
    /**
     * @var $orientation string The orientation of the vehicle, LEFT (default) or RIGHT.
     */
    public $orientation;
}
