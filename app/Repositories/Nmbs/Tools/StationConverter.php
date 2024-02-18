<?php

namespace Irail\Repositories\Nmbs\Tools;

use Irail\Repositories\Nmbs\Models\HafasStation;

class StationConverter
{
    public static function convertIrailStationToStationInfo(HafasStation $iRailStation): HafasStation
    {
        $stationId = substr($iRailStation->id, 8);
        return new HafasStation($stationId, 'http://irail.be/stations/NMBS/' . $stationId,
            $iRailStation->name, $iRailStation->name,
            $iRailStation->locationX, $iRailStation->locationY);
    }
}