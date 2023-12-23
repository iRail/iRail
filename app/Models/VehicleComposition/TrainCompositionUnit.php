<?php

namespace Irail\Models\VehicleComposition;

class TrainCompositionUnit
{
    //-- Computed fields

    /**
     * @var RollingMaterialType
     */
    private RollingMaterialType $materialType;

    //--- Data fields

    /**
     * @var int The UIC code of this vehicle
     */
    private int $uicCode;

    /**
     * @var boolean Indicates if this unit has toilet.
     */
    private bool $hasToilet;

    /**
     * @var boolean Indicates if this unit has a toilet for passengers with reduced mobility.
     */
    private bool $hasPrmToilet;

    /**
     * @var boolean Indicates if this unit has tables.
     */
    private bool $hasTables;

    /**
     * @var boolean Indicates if this unit has 230v power outlets in 1st class.
     */
    private bool $hasFirstClassOutlets;

    /**
     * @var boolean Indicates if this unit has 230v power outlets in 2nd class.
     */
    private bool $hasSecondClassOutlets;

    /**
     * @var boolean Indicates if this unit has heating.
     */
    private bool $hasHeating;

    /**
     * @var boolean Indicates if this unit has air-conditioning.
     */
    private bool $hasAirco;

    /**
     * @var integer The number for this car or motor-unit, visible to the traveler.
     */
    private int $materialNumber;

    private bool $hasBikeSection;

    /**
     * @var string
     */
    private string $tractionType;

    /**
     * @var boolean Whether people can pass to the next unit, backwards in the driving direction.
     */
    private bool $canPassToNextUnit;

    /**
     * @var integer The number of standing positions in second class.
     */
    private int $standingPlacesSecondClass;


    /**
     * @var integer The number of standing positions in first class.
     */
    private int $standingPlacesFirstClass;


    /**
     * @var integer The number of seats in second class coupes.
     */
    private int $seatsCoupeSecondClass;

    /**
     * @var integer The number of seats in first class coupes.
     */
    private int $seatsCoupeFirstClass;

    /**
     * @var integer The number of seats in second class.
     */
    private int $seatsSecondClass;

    /**
     * @var integer The number of seats in first class.
     */
    private int $seatsFirstClass;

    /**
     * @var integer The length of this unit in metric meters.
     */
    private int $lengthInMeter;

    /**
     * @var boolean Indicates if this unit has semi-automatic or manual interior doors
     */
    private bool $hasSemiAutomaticInteriorDoors;

    /**
     * @var boolean Indicates if this unit has a luggage section.
     */
    private bool $hasLuggageSection;

    /**
     * @var string The material subtype name, as specified by the railway company. Examples are AM80_c or M6BUH.
     */
    private string $materialSubTypeName;

    /**
     * @var int The traction group in which this carriage belongs. For example, two trains coupled together have 2 traction positions.
     */
    private int $tractionPosition;

    /**
     * @var boolean Whether this carriage has a section for persons with a reduced mobility.
     */
    private bool $hasPrmSection;
    /**
     * @var boolean Whether this carriage has priority places (places with priority for those who are old, pregnant, ...)
     */
    private bool $hasPriorityPlaces;

    /**
     * @param RollingMaterialType $materialType
     */
    public function __construct(RollingMaterialType $materialType)
    {
        $this->materialType = $materialType;
    }

    public function getMaterialType(): RollingMaterialType
    {
        return $this->materialType;
    }

    public function getUicCode(): int
    {
        return $this->uicCode;
    }

    public function setUicCode(int $uicCode): TrainCompositionUnit
    {
        $this->uicCode = $uicCode;
        return $this;
    }

    public function hasToilet(): bool
    {
        return $this->hasToilet;
    }

    public function setHasToilet(bool $hasToilet): TrainCompositionUnit
    {
        $this->hasToilet = $hasToilet;
        return $this;
    }

    public function hasPrmToilet(): bool
    {
        return $this->hasPrmToilet;
    }

    public function setHasPrmToilet(bool $hasPrmToilet): TrainCompositionUnit
    {
        $this->hasPrmToilet = $hasPrmToilet;
        return $this;
    }

    public function hasTables(): bool
    {
        return $this->hasTables;
    }

    public function setHasTables(bool $hasTables): TrainCompositionUnit
    {
        $this->hasTables = $hasTables;
        return $this;
    }

    public function hasFirstClassOutlets(): bool
    {
        return $this->hasFirstClassOutlets;
    }

    public function setHasFirstClassOutlets(bool $hasFirstClassOutlets): TrainCompositionUnit
    {
        $this->hasFirstClassOutlets = $hasFirstClassOutlets;
        return $this;
    }

    public function hasSecondClassOutlets(): bool
    {
        return $this->hasSecondClassOutlets;
    }

    public function setHasSecondClassOutlets(bool $hasSecondClassOutlets): TrainCompositionUnit
    {
        $this->hasSecondClassOutlets = $hasSecondClassOutlets;
        return $this;
    }

    public function hasHeating(): bool
    {
        return $this->hasHeating;
    }

    public function setHasHeating(bool $hasHeating): TrainCompositionUnit
    {
        $this->hasHeating = $hasHeating;
        return $this;
    }

    public function hasAirco(): bool
    {
        return $this->hasAirco;
    }

    public function setHasAirco(bool $hasAirco): TrainCompositionUnit
    {
        $this->hasAirco = $hasAirco;
        return $this;
    }

    public function getMaterialNumber(): int
    {
        return $this->materialNumber;
    }

    public function setMaterialNumber(int $materialNumber): TrainCompositionUnit
    {
        $this->materialNumber = $materialNumber;
        return $this;
    }

    public function hasBikeSection(): bool
    {
        return $this->hasBikeSection;
    }

    public function setHasBikeSection(bool $hasBikeSection): TrainCompositionUnit
    {
        $this->hasBikeSection = $hasBikeSection;
        return $this;
    }

    public function getTractionType(): string
    {
        return $this->tractionType;
    }

    public function setTractionType(string $tractionType): TrainCompositionUnit
    {
        $this->tractionType = $tractionType;
        return $this;
    }

    public function canPassToNextUnit(): bool
    {
        return $this->canPassToNextUnit;
    }

    public function setCanPassToNextUnit(bool $canPassToNextUnit): TrainCompositionUnit
    {
        $this->canPassToNextUnit = $canPassToNextUnit;
        return $this;
    }

    public function getStandingPlacesSecondClass(): int
    {
        return $this->standingPlacesSecondClass;
    }

    public function setStandingPlacesSecondClass(int $standingPlacesSecondClass): TrainCompositionUnit
    {
        $this->standingPlacesSecondClass = $standingPlacesSecondClass;
        return $this;
    }

    public function getStandingPlacesFirstClass(): int
    {
        return $this->standingPlacesFirstClass;
    }

    public function setStandingPlacesFirstClass(int $standingPlacesFirstClass): TrainCompositionUnit
    {
        $this->standingPlacesFirstClass = $standingPlacesFirstClass;
        return $this;
    }

    public function getSeatsCoupeSecondClass(): int
    {
        return $this->seatsCoupeSecondClass;
    }

    public function setSeatsCoupeSecondClass(int $seatsCoupeSecondClass): TrainCompositionUnit
    {
        $this->seatsCoupeSecondClass = $seatsCoupeSecondClass;
        return $this;
    }

    public function getSeatsCoupeFirstClass(): int
    {
        return $this->seatsCoupeFirstClass;
    }

    public function setSeatsCoupeFirstClass(int $seatsCoupeFirstClass): TrainCompositionUnit
    {
        $this->seatsCoupeFirstClass = $seatsCoupeFirstClass;
        return $this;
    }

    public function getSeatsSecondClass(): int
    {
        return $this->seatsSecondClass;
    }

    public function setSeatsSecondClass(int $seatsSecondClass): TrainCompositionUnit
    {
        $this->seatsSecondClass = $seatsSecondClass;
        return $this;
    }

    public function getSeatsFirstClass(): int
    {
        return $this->seatsFirstClass;
    }

    public function setSeatsFirstClass(int $seatsFirstClass): TrainCompositionUnit
    {
        $this->seatsFirstClass = $seatsFirstClass;
        return $this;
    }

    public function getLengthInMeter(): int
    {
        return $this->lengthInMeter;
    }

    public function setLengthInMeter(int $lengthInMeter): TrainCompositionUnit
    {
        $this->lengthInMeter = $lengthInMeter;
        return $this;
    }

    public function hasSemiAutomaticInteriorDoors(): bool
    {
        return $this->hasSemiAutomaticInteriorDoors;
    }

    public function setHasSemiAutomaticInteriorDoors(bool $hasSemiAutomaticInteriorDoors): TrainCompositionUnit
    {
        $this->hasSemiAutomaticInteriorDoors = $hasSemiAutomaticInteriorDoors;
        return $this;
    }

    public function hasLuggageSection(): bool
    {
        return $this->hasLuggageSection;
    }

    public function setHasLuggageSection(bool $hasLuggageSection): TrainCompositionUnit
    {
        $this->hasLuggageSection = $hasLuggageSection;
        return $this;
    }

    public function getMaterialSubTypeName(): string
    {
        return $this->materialSubTypeName;
    }

    public function setMaterialSubTypeName(string $materialSubTypeName): TrainCompositionUnit
    {
        $this->materialSubTypeName = $materialSubTypeName;
        return $this;
    }

    public function getTractionPosition(): int
    {
        return $this->tractionPosition;
    }

    public function setTractionPosition(int $tractionPosition): TrainCompositionUnit
    {
        $this->tractionPosition = $tractionPosition;
        return $this;
    }

    public function hasPrmSection(): bool
    {
        return $this->hasPrmSection;
    }

    public function setHasPrmSection(bool $hasPrmSection): TrainCompositionUnit
    {
        $this->hasPrmSection = $hasPrmSection;
        return $this;
    }

    public function hasPriorityPlaces(): bool
    {
        return $this->hasPriorityPlaces;
    }

    public function setHasPriorityPlaces(bool $hasPriorityPlaces): TrainCompositionUnit
    {
        $this->hasPriorityPlaces = $hasPriorityPlaces;
        return $this;
    }
}
