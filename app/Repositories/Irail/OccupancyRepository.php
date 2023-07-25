<?php

namespace Irail\Repositories\Irail;

use Irail\Models\DepartureOrArrival;
use Irail\Models\OccupancyInfo;
use Irail\Models\OccupancyLevel;

class OccupancyRepository
{
    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param DepartureOrArrival  $departure
     * @param OccupancyLevel|null $officalNmbsLevel The official NMBS level, if known. If not known, iRail will attempt to read this from previous response data.
     * @return OccupancyInfo
     */
    public static function getOccupancy(DepartureOrArrival $departure, ?OccupancyLevel $officalNmbsLevel = null): OccupancyInfo
    {
        // TODO: implement spitsgids
        return new OccupancyInfo($officalNmbsLevel ?: OccupancyLevel::UNKNOWN, OccupancyLevel::UNKNOWN);
    }
}