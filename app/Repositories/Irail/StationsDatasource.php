<?php

namespace Irail\Repositories\Irail;

use Exception;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Models\StationInfo;
use Irail\Repositories\Nmbs\Models\Station;
use irail\stations\Stations;
use function Irail\Data\Nmbs\str_starts_with;

/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 *
 * This will fetch all stationdata for the NMBS. It implements a couple of standard functions implemented by all stations classes:
 *
 *   * fillDataRoot will fill the entire dataroot with stations
 *   * getStationFromName will return the right station object for a Name
 */
class StationsDatasource
{


    /**
     * Takes a JSON-LD station element and transforms it into the style of the old API.
     */
    private static function transformNewToOldStyle($newstation, $lang)
    {
        $station = new Station();
        $id = str_replace('http://irail.be/stations/NMBS/', '', $newstation->{'@id'});
        $station->id = 'BE.NMBS.' . $id; //old-style iRail ids
        $station->_hafasId = $id;
        $station->locationX = $newstation->longitude;
        $station->locationY = $newstation->latitude;
        $station->{'@id'} = $newstation->{'@id'};

        if (isset($newstation->{'alternative'})) {
            foreach ($newstation->{'alternative'} as $alternatives) {
                if ($alternatives->{'@language'} == strtolower($lang)) {
                    $station->name = $alternatives->{'@value'};
                }
            }
        }
        $station->standardname = $newstation->name;

        if (!isset($station->name)) {
            $station->name = $station->standardname;
        }
        return $station;
    }

    /**
     * Creates a JSON-LD object from an old-style iRail API station object
     */
    public static function transformOldToNewStyle($oldstation)
    {
        $station = [];
        $station['@id'] = $oldstation->{'@id'};
        $station['longitude'] = $oldstation->locationX;
        $station['latitude'] = $oldstation->locationY;
        $station['name'] = $oldstation->name;
        return $station;
    }


    /**
     * @param $id
     * @param $lang
     * @return StationInfo
     * @throws UnknownStopException
     */
    public static function getStationFromID($id, $lang): StationInfo
    {
        $id = str_replace('BE.NMBS.', '', $id);
        if (!str_starts_with($id, '00')) {
            $id = '00' . $id;
        }
        $stationobject = Stations::getStationFromID($id);
        if ($stationobject) {
            return self::transformNewToOldStyle($stationobject, $lang);
        } else {
            throw new UnknownStopException('Could not find a station with id ' . $id . '.', 404);
        }
    }

    /**
     * Gets an appropriate station from the new iRail API.
     *
     * @param $name
     * @param $lang
     * @return Station
     * @throws Exception
     */
    public static function getStationFromName($name, $lang)
    {
        //first check if it wasn't by any chance an id
        if (str_starts_with($name, '0') || str_starts_with($name, 'BE.NMBS') || str_starts_with($name, 'http://')) {
            return self::getStationFromID($name, $lang);
        }

        $name = html_entity_decode($name, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $name = preg_replace('/[ ]?\([a-zA-Z]+\)/', '', $name);
        $name = str_replace(' [NMBS/SNCB]', '', $name);
        $name = str_replace(' `', '', $name);
        $name = explode('/', $name);
        $name = trim($name[0]);
        $stationsgraph = Stations::getStations($name);
        if (!isset($stationsgraph->{'@graph'}[0])) {
            throw new Exception('Could not match \'' . $name . '\' with a station id in iRail. Please report this issue at https://github.com/irail/stations/issues/new if you think we should support your query.');
        }
        $station = $stationsgraph->{'@graph'}[0];

        //or find exact match using ugly breaks and strlen
        foreach ($stationsgraph->{'@graph'} as $stationitem) {
            if (strlen($stationitem->name) === strlen($name)) {
                $station = $stationitem;
                break;
            } else {
                if (isset($stationitem->alternative) && is_array($stationitem->alternative)) {
                    foreach ($stationitem->alternative as $alt) {
                        if (strlen($alt->{'@value'}) === strlen($name)) {
                            $station = $stationitem;
                            break;
                        }
                    }
                } else {
                    if (isset($stationitem->alternative) && strlen($stationitem->alternative->{'@value'}) === strlen($name)) {
                        $station = $stationitem;
                        break;
                    }
                }
            }
        }
        return self::transformNewToOldStyle($station, $lang);
    }

    /**
     * @param $lang
     * @return array
     * @throws Exception
     */
    private static function fetchAllStationsFromDB($lang)
    {
        $stations = [];
        $newstations = Stations::getStations();
        foreach ($newstations->{'@graph'} as $station) {
            array_push($stations, self::transformNewToOldStyle($station, $lang));
        }

        return $stations;
    }
}
