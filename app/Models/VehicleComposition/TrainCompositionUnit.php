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
     * @var boolean Indicates if this unit has toilets.
     */
    private bool $hasToilets;

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

    /**
     * @return RollingMaterialType
     */
    public function getMaterialType(): RollingMaterialType
    {
        return $this->materialType;
    }

    /**
     * @return bool
     */
    public function hasToilets(): bool
    {
        return $this->hasToilets;
    }

    /**
     * @param bool $hasToilets
     */
    public function setHasToilets(bool $hasToilets): void
    {
        $this->hasToilets = $hasToilets;
    }

    /**
     * @return bool
     */
    public function hasTables(): bool
    {
        return $this->hasTables;
    }

    /**
     * @param bool $hasTables
     */
    public function setHasTables(bool $hasTables): void
    {
        $this->hasTables = $hasTables;
    }

    /**
     * @return bool
     */
    public function hasFirstClassOutlets(): bool
    {
        return $this->hasFirstClassOutlets;
    }

    /**
     * @param bool $hasFirstClassOutlets
     */
    public function setHasFirstClassOutlets(bool $hasFirstClassOutlets): void
    {
        $this->hasFirstClassOutlets = $hasFirstClassOutlets;
    }

    /**
     * @return bool
     */
    public function hasSecondClassOutlets(): bool
    {
        return $this->hasSecondClassOutlets;
    }

    /**
     * @param bool $hasSecondClassOutlets
     */
    public function setHasSecondClassOutlets(bool $hasSecondClassOutlets): void
    {
        $this->hasSecondClassOutlets = $hasSecondClassOutlets;
    }

    /**
     * @return bool
     */
    public function hasHeating(): bool
    {
        return $this->hasHeating;
    }

    /**
     * @param bool $hasHeating
     */
    public function setHasHeating(bool $hasHeating): void
    {
        $this->hasHeating = $hasHeating;
    }

    /**
     * @return bool
     */
    public function hasAirco(): bool
    {
        return $this->hasAirco;
    }

    /**
     * @param bool $hasAirco
     */
    public function setHasAirco(bool $hasAirco): void
    {
        $this->hasAirco = $hasAirco;
    }

    /**
     * @return bool
     */
    public function hasBikeSection(): bool
    {
        return $this->hasBikeSection;
    }

    public function setHasBikeSection(bool $hasBikeSection)
    {
        $this->hasBikeSection = $hasBikeSection;
    }

    /**
     * @return int
     */
    public function getMaterialNumber(): int
    {
        return $this->materialNumber;
    }

    /**
     * @param int $materialNumber
     */
    public function setMaterialNumber(int $materialNumber): void
    {
        $this->materialNumber = $materialNumber;
    }

    /**
     * @return string
     */
    public function getTractionType(): string
    {
        return $this->tractionType;
    }

    /**
     * @param string $tractionType
     */
    public function setTractionType(string $tractionType): void
    {
        $this->tractionType = $tractionType;
    }

    /**
     * @return bool
     */
    public function isCanPassToNextUnit(): bool
    {
        return $this->canPassToNextUnit;
    }

    /**
     * @param bool $canPassToNextUnit
     */
    public function setCanPassToNextUnit(bool $canPassToNextUnit): void
    {
        $this->canPassToNextUnit = $canPassToNextUnit;
    }

    /**
     * @return int
     */
    public function getStandingPlacesSecondClass(): int
    {
        return $this->standingPlacesSecondClass;
    }

    /**
     * @param int $standingPlacesSecondClass
     */
    public function setStandingPlacesSecondClass(int $standingPlacesSecondClass): void
    {
        $this->standingPlacesSecondClass = $standingPlacesSecondClass;
    }

    /**
     * @return int
     */
    public function getStandingPlacesFirstClass(): int
    {
        return $this->standingPlacesFirstClass;
    }

    /**
     * @param int $standingPlacesFirstClass
     */
    public function setStandingPlacesFirstClass(int $standingPlacesFirstClass): void
    {
        $this->standingPlacesFirstClass = $standingPlacesFirstClass;
    }

    /**
     * @return int
     */
    public function getSeatsCoupeSecondClass(): int
    {
        return $this->seatsCoupeSecondClass;
    }

    /**
     * @param int $seatsCoupeSecondClass
     */
    public function setSeatsCoupeSecondClass(int $seatsCoupeSecondClass): void
    {
        $this->seatsCoupeSecondClass = $seatsCoupeSecondClass;
    }

    /**
     * @return int
     */
    public function getSeatsCoupeFirstClass(): int
    {
        return $this->seatsCoupeFirstClass;
    }

    /**
     * @param int $seatsCoupeFirstClass
     */
    public function setSeatsCoupeFirstClass(int $seatsCoupeFirstClass): void
    {
        $this->seatsCoupeFirstClass = $seatsCoupeFirstClass;
    }

    /**
     * @return int
     */
    public function getSeatsSecondClass(): int
    {
        return $this->seatsSecondClass;
    }

    /**
     * @param int $seatsSecondClass
     */
    public function setSeatsSecondClass(int $seatsSecondClass): void
    {
        $this->seatsSecondClass = $seatsSecondClass;
    }

    /**
     * @return int
     */
    public function getSeatsFirstClass(): int
    {
        return $this->seatsFirstClass;
    }

    /**
     * @param int $seatsFirstClass
     */
    public function setSeatsFirstClass(int $seatsFirstClass): void
    {
        $this->seatsFirstClass = $seatsFirstClass;
    }

    /**
     * @return int
     */
    public function getLengthInMeter(): int
    {
        return $this->lengthInMeter;
    }

    /**
     * @param int $lengthInMeter
     */
    public function setLengthInMeter(int $lengthInMeter): void
    {
        $this->lengthInMeter = $lengthInMeter;
    }

    /**
     * @return bool
     */
    public function hasSemiAutomaticInteriorDoors(): bool
    {
        return $this->hasSemiAutomaticInteriorDoors;
    }

    /**
     * @param bool $hasSemiAutomaticInteriorDoors
     */
    public function setHasSemiAutomaticInteriorDoors(bool $hasSemiAutomaticInteriorDoors): void
    {
        $this->hasSemiAutomaticInteriorDoors = $hasSemiAutomaticInteriorDoors;
    }

    /**
     * @return bool
     */
    public function hasLuggageSection(): bool
    {
        return $this->hasLuggageSection;
    }

    /**
     * @param bool $hasLuggageSection
     */
    public function setHasLuggageSection(bool $hasLuggageSection): void
    {
        $this->hasLuggageSection = $hasLuggageSection;
    }

    /**
     * @return string
     */
    public function getMaterialSubTypeName(): string
    {
        return $this->materialSubTypeName;
    }

    /**
     * @param string $materialSubTypeName
     */
    public function setMaterialSubTypeName(string $materialSubTypeName): void
    {
        $this->materialSubTypeName = $materialSubTypeName;
    }

    /**
     * @return int
     */
    public function getTractionPosition(): int
    {
        return $this->tractionPosition;
    }

    /**
     * @param int $tractionPosition
     */
    public function setTractionPosition(int $tractionPosition): void
    {
        $this->tractionPosition = $tractionPosition;
    }

    /**
     * @return bool
     */
    public function hasPrmSection(): bool
    {
        return $this->hasPrmSection;
    }

    /**
     * @param bool $hasPrmSection
     */
    public function setHasPrmSection(bool $hasPrmSection): void
    {
        $this->hasPrmSection = $hasPrmSection;
    }

    /**
     * @return bool
     */
    public function hasPriorityPlaces(): bool
    {
        return $this->hasPriorityPlaces;
    }

    /**
     * @param bool $hasPriorityPlaces
     */
    public function setHasPriorityPlaces(bool $hasPriorityPlaces): void
    {
        $this->hasPriorityPlaces = $hasPriorityPlaces;
    }


}
