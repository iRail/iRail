<?php

namespace Irail\Repositories\Irail;

use Exception;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Models\StationInfo;
use irail\stations\Stations;

class StationsRepository
{
    use \Irail\Traits\Cache;
    private string $lang = 'en';

    public function __construct()
    {
        $this->setCachePrefix('StationsRepository');
    }

    /**
     * @throws UnknownStopException
     */
    public function getStationById(string $id): StationInfo
    {
        $result = $this->getCacheWithDefaultCacheUpdate("getStationById|$id", function () use ($id): StationInfo {
            $station = Stations::getStationFromID($id);
            if ($station == null) {
                throw new UnknownStopException(500, "Could not match id '{$id}' with a station in iRail. "
                    . 'Please report this issue at https://github.com/irail/stations/issues/new if you think we should support your query.');
            }
            return $this->graphStationToStationInfo($station);
        }, 3600 * 12);
        return $result->getValue();
    }

    public function getStationByHafasId(string $id): ?StationInfo
    {
        return $this->getStationById('00' . $id);
    }

    /** @noinspection PhpUnused Used through dependency injection */
    public function findStationByName(string $name): ?StationInfo
    {
        $cacheKey = "stationsRepository.findStationByName.$name";
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue) {
            return $cachedValue;
        }

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
            return null;
        }
        $bestMatch = $stationsgraph->{'@graph'}[0];

        // Legacy logic from StationsDatasource
        // or find exact match using ugly breaks and strlen
        foreach ($stationsgraph->{'@graph'} as $stationitem) {
            if (strlen($stationitem->name) === strlen($name)) {
                // If we have a near-exact match (based on string length), pick this one
                $bestMatch = $stationitem;
                break;
            } elseif (isset($stationitem->alternative) && is_array($stationitem->alternative)) {
                // If one of the alternative names if a near-exact match (based on string length), pick it.
                foreach ($stationitem->alternative as $alt) {
                    if (strlen($alt->{'@value'}) === strlen($name)) {
                        $bestMatch = $stationitem;
                        break;
                    }
                }
            } elseif (isset($stationitem->alternative) && strlen($stationitem->alternative->{'@value'}) === strlen($name)) {
                // If there is only one alternative name, but it matches on string length, pick it.
                $bestMatch = $stationitem;
                break;
            }
        }
        $result = $this->graphStationToStationInfo($bestMatch);
        Cache::put($cacheKey, $result, 12 * 3600);
        return $result;
    }

    /**
     * @return StationInfo[]
     */
    public function findAllStations(): array
    {
        // TODO: implement
        throw new Exception('TODO: Implement');
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
        if (isset($iRailGraphStation->{'alternative'})) {
            foreach ($iRailGraphStation->{'alternative'} as $alternatives) {
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
