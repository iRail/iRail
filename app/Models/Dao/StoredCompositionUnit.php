<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StoredCompositionUnit extends Model
{

    protected $table = 'CompositionUnit';
    protected $primaryKey = 'uicCode';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    /**
     * @return int The UIC code, for example 508826960330
     */
    public function getUicCode(): int
    {
        return $this->getAttribute('uicCode');
    }

    public function setUicCode(int $uicCode): StoredCompositionUnit
    {
        $this->setAttribute('uicCode', $uicCode);
        return $this;
    }

    /**
     * @return string The vehicle type, for example "M7"
     */
    public function getMaterialTypeName(): string
    {
        return $this->getAttribute('materialTypeName');
    }

    public function setMaterialTypeName(string $materialTypeName): StoredCompositionUnit
    {
        $this->setAttribute('materialTypeName', $materialTypeName);
        return $this;
    }

    /**
     * @return string The vehicle subtype, for example "M7BUH"
     */
    public function getMaterialSubTypeName(): string
    {
        return $this->getAttribute('materialSubTypeName');
    }

    public function setMaterialSubTypeName(string $materialSubTypeName): StoredCompositionUnit
    {
        $this->setAttribute('materialSubTypeName', $materialSubTypeName);
        return $this;
    }

    /**
     * @return int The vehicle number, for example "72033"
     */
    public function getMaterialNumber(): int
    {
        return $this->getAttribute('materialNumber');
    }

    public function setMaterialNumber(int $materialNumber): StoredCompositionUnit
    {
        $this->setAttribute('materialNumber', $materialNumber);
        return $this;
    }

    /**
     * @return bool Whether a toilet is available
     */
    public function hasToilet(): bool
    {
        return $this->getAttribute('hasToilet');
    }

    public function setHasToilet(bool $hasToilet): StoredCompositionUnit
    {
        $this->setAttribute('hasToilet', $hasToilet);
        return $this;
    }

    /**
     * @return bool Whether a toilet accessible for passengers with reduced mobility is available
     */
    public function hasPrmToilet(): bool
    {
        return $this->getAttribute('hasPrmToilet');
    }

    public function setHasPrmToilet(bool $hasPrmToilet): StoredCompositionUnit
    {
        $this->setAttribute('hasPrmToilet', $hasPrmToilet);
        return $this;
    }

    /**
     * @return bool Whether air conditioning is available
     */
    public function hasAirco(): bool
    {
        return $this->getAttribute('hasAirco');
    }

    public function setHasAirco(bool $hasAirco): StoredCompositionUnit
    {
        $this->setAttribute('hasAirco', $hasAirco);
        return $this;
    }

    /**
     * @return bool Whether a section for bikes is present
     */
    public function hasBikeSection(): bool
    {
        return $this->getAttribute('hasBikeSection');
    }

    public function setHasBikeSection(bool $hasBikeSection): StoredCompositionUnit
    {
        $this->setAttribute('hasBikeSection', $hasBikeSection);
        return $this;
    }

    /**
     * @return bool Whether a section for passengers with reduced mobility is present
     */
    public function hasPrmSection(): bool
    {
        return $this->getAttribute('hasPrmSection');
    }

    public function setHasPrmSection(bool $hasPrmSection): StoredCompositionUnit
    {
        $this->setAttribute('hasPrmSection', $hasPrmSection);
        return $this;
    }

    /**
     * @return int The number of seats in first class
     */
    public function getSeatsFirstClass(): int
    {
        return $this->getAttribute('seatsFirstClass');
    }

    public function setSeatsFirstClass(int $seatsFirstClass): StoredCompositionUnit
    {
        $this->setAttribute('seatsFirstClass', $seatsFirstClass);
        return $this;
    }

    /**
     * @return int The number of seats in second class
     */
    public function getSeatsSecondClass(): int
    {
        return $this->getAttribute('seatsSecondClass');
    }

    public function setSeatsSecondClass(int $seatsSecondClass): StoredCompositionUnit
    {
        $this->setAttribute('seatsSecondClass', $seatsSecondClass);
        return $this;
    }

    /**
     * @return Carbon The time when this unit was first seen
     */
    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute('createdAt');
    }

    // setCreatedAt is provided by the parent class

    /**
     * @return Carbon The time when this unit was last updated
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute('updatedAt');
    }

    // setUpdatedAt is provided by the parent class

}