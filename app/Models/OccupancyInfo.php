<?php

namespace Irail\Models;

class OccupancyInfo
{
    private OccupancyLevel $officialLevel;
    private OccupancyLevel $spitsgidsLevel;

    /**
     * @param OccupancyLevel $officialLevel
     * @param OccupancyLevel $spitsgidsLevel
     */
    public function __construct(OccupancyLevel $officialLevel, OccupancyLevel $spitsgidsLevel)
    {
        $this->officialLevel = $officialLevel;
        $this->spitsgidsLevel = $spitsgidsLevel;
    }

    /**
     * @return OccupancyLevel
     */
    public function getOfficialLevel(): OccupancyLevel
    {
        return $this->officialLevel;
    }

    /**
     * @param OccupancyLevel $officialLevel
     */
    public function setOfficialLevel(OccupancyLevel $officialLevel): void
    {
        $this->officialLevel = $officialLevel;
    }

    /**
     * @return OccupancyLevel
     */
    public function getSpitsgidsLevel(): OccupancyLevel
    {
        return $this->spitsgidsLevel;
    }

    /**
     * @param OccupancyLevel $spitsgidsLevel
     */
    public function setSpitsgidsLevel(OccupancyLevel $spitsgidsLevel): void
    {
        $this->spitsgidsLevel = $spitsgidsLevel;
    }


}