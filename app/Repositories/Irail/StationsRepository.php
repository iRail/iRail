<?php

namespace Irail\Repositories\Irail;

use Exception;
use InvalidArgumentException;
use Irail\Models\StationInfo;
use irail\stations\Stations;
use function Irail\Data\Nmbs\Repositories\str_starts_with;

class StationsRepository
{
    private string $lang = 'en';

    public function getStationById(string $id): ?StationInfo
    {
        $station = Stations::getStationFromID($id);
        if ($station == null) {
            throw new Exception("Could not match id '{$id}' with a station in iRail."
                . 'Please report this issue at https://github.com/irail/stations/issues/new if you think we should support your query.');
        }
        return $this->graphStationToStationInfo($station);
    }

    public function findStationByName(string $name): ?StationInfo
    {
        // first check if it wasn't by any chance an id
        if (str_starts_with($name, '0') || str_starts_with($name, 'BE.NMBS') || str_starts_with($name, 'http://')) {
            throw new InvalidArgumentException("Expected station name, got station id: {$name}", 500);
        }

        $name = html_entity_decode($name, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $name = preg_replace('/[ ]?\([a-zA-Z]+\)/', '', $name);
        $name = str_replace(' [NMBS/SNCB]', '', $name);
        $name = str_replace(' `', '', $name);
        $name = explode('/', $name);
        $name = trim($name[0]);

        $stationsgraph = Stations::getStations($name);
        if (!isset($stationsgraph->{'@graph'}[0])) {
            throw new Exception("Could not match '{$name}' with a station id in iRail."
                . 'Please report this issue at https://github.com/irail/stations/issues/new if you think we should support your query.');
        }
        $bestMatch = $stationsgraph->{'@graph'}[0];

        // Legacy logic from StationsDatasource
        // or find exact match using ugly breaks and strlen
        foreach ($stationsgraph->{'@graph'} as $stationitem) {
            if (strlen($stationitem->name) === strlen($name)) {
                // If we have a near-exact match (based on string length), pick this one
                $bestMatch = $stationitem;
                break;
            } else if (isset($stationitem->alternative) && is_array($stationitem->alternative)) {
                // If one of the alternative names if a near-exact match (based on string length), pick it.
                foreach ($stationitem->alternative as $alt) {
                    if (strlen($alt->{'@value'}) === strlen($name)) {
                        $bestMatch = $stationitem;
                        break;
                    }
                }
            } else if (isset($stationitem->alternative) && strlen($stationitem->alternative->{'@value'}) === strlen($name)) {
                // If there is only one alternative name, but it matches on string length, pick it.
                $bestMatch = $stationitem;
                break;
            }
        }
        return $this->graphStationToStationInfo($bestMatch);
    }

    /**
     * @return StationInfo[]
     */
    public function findAllStations(): array
    {
        return [];
    }

    public function setLocalizedLanguage(string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @param $iRailGraphStation
     * @return StationInfo
     */
    private function graphStationToStationInfo($iRailGraphStation): StationInfo
    {
        $localName = $iRailGraphStation->{'name'};
        $translatedName = $localName;
        if (isset($newstation->{'alternative'})) {
            foreach ($newstation->{'alternative'} as $alternatives) {
                if ($alternatives->{'@language'} == $this->lang) {
                    $translatedName = $alternatives->{'@value'};
                }
            }
        }

        return new StationInfo(
            str_replace('http://irail.be/stations/NMBS/', '', $iRailGraphStation->{'@id'}),
            $iRailGraphStation->{'@id'},
            $localName,
            $translatedName,
            $iRailGraphStation->longitude,
            $iRailGraphStation->latitude,
        );
    }
}
