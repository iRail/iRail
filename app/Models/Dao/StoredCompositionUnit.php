<?php

namespace Irail\Models\Dao;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StoredCompositionUnit extends Model
{

    protected $table = 'composition_unit';
    protected $primaryKey = 'uic_code';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return int The UIC code, for example 508826960330
     */
    public function getUicCode(): int
    {
        return $this->getAttribute('uic_code');
    }

    public function setUicCode(int $uicCode): StoredCompositionUnit
    {
        $this->setAttribute('uic_code', $uicCode);
        return $this;
    }

    /**
     * @return string The vehicle type, for example "M7"
     */
    public function getMaterialTypeName(): string
    {
        return $this->getAttribute('material_type_name');
    }

    public function setMaterialTypeName(string $materialTypeName): StoredCompositionUnit
    {
        $this->setAttribute('material_type_name', $materialTypeName);
        return $this;
    }

    /**
     * @return string The vehicle subtype, for example "M7BUH"
     */
    public function getMaterialSubTypeName(): string
    {
        return $this->getAttribute('material_subtype_name');
    }

    public function setMaterialSubTypeName(string $materialSubTypeName): StoredCompositionUnit
    {
        $this->setAttribute('material_subtype_name', $materialSubTypeName);
        return $this;
    }

    /**
     * @return int The vehicle number, for example "72033"
     */
    public function getMaterialNumber(): int
    {
        return $this->getAttribute('material_number');
    }

    public function setMaterialNumber(int $materialNumber): StoredCompositionUnit
    {
        $this->setAttribute('material_number', $materialNumber);
        return $this;
    }

    /**
     * @return bool Whether a toilet is available
     */
    public function hasToilet(): bool
    {
        return $this->getAttribute('has_toilet');
    }

    public function setHasToilet(bool $hasToilet): StoredCompositionUnit
    {
        $this->setAttribute('has_toilet', $hasToilet);
        return $this;
    }

    /**
     * @return bool Whether a toilet accessible for passengers with reduced mobility is available
     */
    public function hasPrmToilet(): bool
    {
        return $this->getAttribute('has_prm_toilet');
    }

    public function setHasPrmToilet(bool $hasPrmToilet): StoredCompositionUnit
    {
        $this->setAttribute('has_prm_toilet', $hasPrmToilet);
        return $this;
    }

    /**
     * @return bool Whether air conditioning is available
     */
    public function hasAirco(): bool
    {
        return $this->getAttribute('has_airco');
    }

    public function setHasAirco(bool $hasAirco): StoredCompositionUnit
    {
        $this->setAttribute('has_airco', $hasAirco);
        return $this;
    }

    /**
     * @return bool Whether a section for bikes is present
     */
    public function hasBikeSection(): bool
    {
        return $this->getAttribute('has_bike_section');
    }

    public function setHasBikeSection(bool $hasBikeSection): StoredCompositionUnit
    {
        $this->setAttribute('has_bike_section', $hasBikeSection);
        return $this;
    }

    /**
     * @return bool Whether a section for passengers with reduced mobility is present
     */
    public function hasPrmSection(): bool
    {
        return $this->getAttribute('has_prm_section');
    }

    public function setHasPrmSection(bool $hasPrmSection): StoredCompositionUnit
    {
        $this->setAttribute('has_prm_section', $hasPrmSection);
        return $this;
    }

    /**
     * @return int The number of seats in first class
     */
    public function getSeatsFirstClass(): int
    {
        return $this->getAttribute('seats_first_class');
    }

    public function setSeatsFirstClass(int $seatsFirstClass): StoredCompositionUnit
    {
        $this->setAttribute('seats_first_class', $seatsFirstClass);
        return $this;
    }

    /**
     * @return int The number of seats in second class
     */
    public function getSeatsSecondClass(): int
    {
        return $this->getAttribute('seats_second_class');
    }

    public function setSeatsSecondClass(int $seatsSecondClass): StoredCompositionUnit
    {
        $this->setAttribute('seats_second_class', $seatsSecondClass);
        return $this;
    }

    /**
     * @return Carbon The time when this unit was first seen
     */
    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute('created_at');
    }

    // setCreatedAt is provided by the parent class

    /**
     * @return Carbon The time when this unit was last updated
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute('updated_at');
    }

    // setUpdatedAt is provided by the parent class

}