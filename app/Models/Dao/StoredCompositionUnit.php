<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;

class StoredCompositionUnit
{
    private int $uicCode;
    private string $materialTypeName;
    private string $materialSubTypeName;
    private int $materialNumber;
    private bool $hasToilet;
    private bool $hasPrmToilet;
    private bool $hasAirco;
    private bool $hasBikeSection;
    private bool $hasPrmSection;
    private int $seatsFirstClass;
    private int $seatsSecondClass;
    private Carbon $createdAt;
    private Carbon $updatedAt;

    /**
     * @return int The UIC code, for example 508826960330
     */
    public function getUicCode(): int
    {
        return $this->uicCode;
    }

    public function setUicCode(int $uicCode): StoredCompositionUnit
    {
        $this->uicCode = $uicCode;
        return $this;
    }

    /**
     * @return string The vehicle type, for example "M7"
     */
    public function getMaterialTypeName(): string
    {
        return $this->materialTypeName;
    }

    public function setMaterialTypeName(string $materialTypeName): StoredCompositionUnit
    {
        $this->materialTypeName = $materialTypeName;
        return $this;
    }

    /**
     * @return string The vehicle subtype, for example "M7BUH"
     */
    public function getMaterialSubTypeName(): string
    {
        return $this->materialSubTypeName;
    }

    public function setMaterialSubTypeName(string $materialSubTypeName): StoredCompositionUnit
    {
        $this->materialSubTypeName = $materialSubTypeName;
        return $this;
    }

    /**
     * @return int The vehicle number, for example "72033"
     */
    public function getMaterialNumber(): int
    {
        return $this->materialNumber;
    }

    public function setMaterialNumber(int $materialNumber): StoredCompositionUnit
    {
        $this->materialNumber = $materialNumber;
        return $this;
    }

    /**
     * @return bool Whether a toilet is available
     */
    public function hasToilet(): bool
    {
        return $this->hasToilet;
    }

    public function setHasToilet(bool $hasToilet): StoredCompositionUnit
    {
        $this->hasToilet = $hasToilet;
        return $this;
    }

    /**
     * @return bool Whether a toilet accessible for passengers with reduced mobility is available
     */
    public function hasPrmToilet(): bool
    {
        return $this->hasPrmToilet;
    }

    public function setHasPrmToilet(bool $hasPrmToilet): StoredCompositionUnit
    {
        $this->hasPrmToilet = $hasPrmToilet;
        return $this;
    }

    /**
     * @return bool Whether air conditioning is available
     */
    public function hasAirco(): bool
    {
        return $this->hasAirco;
    }

    public function setHasAirco(bool $hasAirco): StoredCompositionUnit
    {
        $this->hasAirco = $hasAirco;
        return $this;
    }

    /**
     * @return bool Whether a section for bikes is present
     */
    public function hasBikeSection(): bool
    {
        return $this->hasBikeSection;
    }

    public function setHasBikeSection(bool $hasBikeSection): StoredCompositionUnit
    {
        $this->hasBikeSection = $hasBikeSection;
        return $this;
    }

    /**
     * @return bool Whether a section for passengers with reduced mobility is present
     */
    public function hasPrmSection(): bool
    {
        return $this->hasPrmSection;
    }

    public function setHasPrmSection(bool $hasPrmSection): StoredCompositionUnit
    {
        $this->hasPrmSection = $hasPrmSection;
        return $this;
    }

    /**
     * @return int The number of seats in first class
     */
    public function getSeatsFirstClass(): int
    {
        return $this->seatsFirstClass;
    }

    public function setSeatsFirstClass(int $seatsFirstClass): StoredCompositionUnit
    {
        $this->seatsFirstClass = $seatsFirstClass;
        return $this;
    }

    /**
     * @return int The number of seats in second class
     */
    public function getSeatsSecondClass(): int
    {
        return $this->seatsSecondClass;
    }

    public function setSeatsSecondClass(int $seatsSecondClass): StoredCompositionUnit
    {
        $this->seatsSecondClass = $seatsSecondClass;
        return $this;
    }

    /**
     * @return Carbon The time when this unit was first seen
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): StoredCompositionUnit
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Carbon The time when this unit was last updated
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(Carbon $updatedAt): StoredCompositionUnit
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

}