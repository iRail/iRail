<?php

namespace Irail\api\data\models;

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