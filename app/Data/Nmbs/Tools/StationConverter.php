<?php

namespace Irail\Data\Nmbs\Tools;

use Irail\Data\Nmbs\Models\Station;
use Irail\Models\StationInfo;

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