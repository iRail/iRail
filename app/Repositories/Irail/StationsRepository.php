<?php

namespace Irail\Repositories\Irail;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Models\Station;
use Irail\Stations\Station as StationsCsvStation;
use irail\stations\Stations as StationsCsv;

class StationsRepository
{
    private string $lang = 'en';

    public function __construct()
    {
    }

    /**
     * @throws UnknownStopException
     */
    public function getStationById(string $id): Station
    {
        $cacheKey = "getStationById|$id";
        $cachedValue = Cache::get($cacheKey);
        if ($cachedValue) {
            return $cachedValue;
        }

        $station = StationsCsv::getStationFromID($id);
        if ($station == null) {
            throw new UnknownStopException(500, "Could not match id '{$id}' with a station in iRail. "
                . 'Please report this issue at https://github.com/irail/stations/issues/new if you think we should support your query.');
        }
        $result = $this->stationsCsvToStation($station);

        Cache::put($cacheKey, $result, 3600 * 12);
        return $result;
    }

    public function getStationByHafasId(string $id): ?Station
    {
        return $this->getStationById('00' . $id);
    }

    /** @noinspection PhpUnused Used through dependency injection */
    public function findStationByName(string $name): ?Station
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

        $stations = StationsCsv::getStations($name);
        if (empty($stations)) {
            return null;
        }
        $bestMatch = $stations[0]; // Stations are already sorted in the stations package
        $result = $this->stationsCsvToStation($bestMatch);
        Cache::put($cacheKey, $result, 12 * 3600);
        return $result;
    }

    /**
     * @return Station[]
     */
    public function findAllStations(): array
    {
        return array_map(fn($stationCsv) => self::stationsCsvToStation($stationCsv), StationsCsv::getStations());
    }

    public function setLocalizedLanguage(string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @param StationsCsvStation $stationsCsvStation
     * @return Station
     */
    private function stationsCsvToStation(StationsCsvStation $stationsCsvStation): Station
    {
        $localNames = $stationsCsvStation->getLocalizedNames();

        return new Station(
            str_replace('http://irail.be/stations/NMBS/', '', $stationsCsvStation->getUri()),
            $stationsCsvStation->getUri(),
            $stationsCsvStation->getName(),
            key_exists($this->lang, $localNames) ? $localNames[$this->lang] : $stationsCsvStation->getName(),
            $stationsCsvStation->getLongitude(),
            $stationsCsvStation->getLatitude(),
        );
    }
}
