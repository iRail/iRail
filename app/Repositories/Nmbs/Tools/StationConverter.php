<?php

namespace Irail\Repositories\Nmbs\Tools;

use Irail\Models\StationInfo;
use Irail\Repositories\Nmbs\Models\Station;

class StationConverter
{
    public static function convertIrailStationToStationInfo(Station $iRailStation): StationInfo
    {
        $stationId = substr($iRailStation->id, 8);
        return new StationInfo($stationId, 'http://irail.be/stations/NMBS/' . $stationId,
            $iRailStation->name, $iRailStation->name,
            $iRailStation->locationX, $iRailStation->locationY);
    }
}