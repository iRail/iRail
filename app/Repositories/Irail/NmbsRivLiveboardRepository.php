<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 */

namespace Irail\Repositories\Irail;

use DateTime;
use Exception;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Occupancy;
use Irail\Models\PlatformInfo;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\Nmbs\StationsRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;

class NmbsRivLiveboardRepository implements LiveboardRepository
{
    private StationsRepository $stationsRepository;
    private NmbsRivRawDataRepository $rivDataRepository;

    public function __construct(StationsRepository $stationsRepository, NmbsRivRawDataRepository $rivDataRepository = null)
    {
        $this->stationsRepository = $stationsRepository;
        if ($rivDataRepository != null) {
            $this->rivDataRepository = $rivDataRepository;
        } else {
            $this->rivDataRepository = new NmbsRivRawDataRepository($this->stationsRepository);
        }
    }

    /**
     * This is the entry point for the data fetching and transformation.
     *
     * @param LiveboardRequest $request
     * @return LiveboardSearchResult
     * @throws Exception
     */
    public function getLiveboard(LiveboardRequest $request): LiveboardSearchResult
    {
        $rawData = $this->rivDataRepository->getLiveboardData($request);
        $this->stationsRepository->setLocalizedLanguage($request->getLanguage());
        return $this->parseNmbsRawData($request, $rawData);
    }

    private function parseNmbsRawData(LiveboardRequest $request, CachedData $cachedRawData): LiveboardSearchResult
    {
        $rawData = $cachedRawData->getValue();

        if (empty($serverData)) {
            throw new Exception('The server did not return any data.', 500);
        }

        // Now we'll actually read the departures/arrivals information.
        $currentStation = $this->stationsRepository->getStationById($request->getStationId());
        if ($currentStation == null) {
            throw new Exception("Failed to match station id {$request->getStationId()} with a known station",
                500);
        }
        $decodedJsonData = json_decode($rawData, associative: true);
        $stopsAtStation = $decodedJsonData['entries'];
        $departuresOrArrivals = [];
        foreach ($stopsAtStation as $stop) {
            $departuresOrArrivals[] = $this->parseStopAtStation($request, $currentStation, $stop);
        }

        $liveboardSearchResult = new LiveboardSearchResult($currentStation, $departuresOrArrivals);
        $liveboardSearchResult->mergeCacheValidity($cachedRawData->getCreatedAt(), $cachedRawData->getExpiresAt());
        return $liveboardSearchResult;
    }

    /**
     * Parse the JSON data received from the NMBS.
     *
     * @param LiveboardRequest $request
     * @param StationInfo      $currentStation
     * @param array            $stop
     * @return DepartureOrArrival
     * @throws Exception
     */
    private function parseStopAtStation(LiveboardRequest $request, StationInfo $currentStation, array $stop): DepartureOrArrival
    {

        /*
         * {
         *    "EntryDate": "2022-11-07 19:55:00",
         *    "UicCode": "8821006",
         *    "TrainNumber": 9267,
         *    "CommercialType": "IC",
         *    "PlannedDeparture": "2022-11-07 20:44:00",
         *    "DestinationNl": "BREDA (NL) Amsterdam Cs (NL)",
         *    "DestinationFr": "BREDA (NL) Amsterdam Cs (NL)",
         *    "Platform": "22",
         *    "ExpectedDeparture": "2022-11-07 21:26:00",
         *    "DepartureDelay": "00:42:00",
         *    "Destination1UicCode": "8400058"
         *  },
         */
        $isArrivalBoard = $request->getDepartureArrivalMode() == TimeSelection::ARRIVAL;

        // The date of this departure
        $plannedDateTime = DateTime::createFromFormat('Y-m-d H:i:s',
            $isArrivalBoard ? $stop['PlannedArrival'] : $stop['PlannedDeparture']
        );
        $unixtime = $plannedDateTime->getTimestamp();

        $delay = self::parseDelayInSeconds($isArrivalBoard ? $stop['ArrivalDelay'] : $stop['DepartureDelay']);

        // parse the scheduled time of arrival/departure and the vehicle (which is returned as a number to look up in the vehicle definitions list)
        // $hafasVehicle] = self::parseScheduledTimeAndVehicle($stop, $date, $vehicleDefinitions);
        // parse information about which platform this train will depart from/arrive to.
        $platform = $stop['Platform'];
        $isPlatformNormal = 1; // TODO:  reverse-engineer and implement

        // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
        // DepartureCanceled gives information if this stop has been canceled.
        $stopCanceled = 0; // TODO: reverse-engineer and implement
        $left = 0; // TODO: probably no longer supported

        $isExtraTrain = 0; // TODO: probably no longer supported
        if (key_exists('status', $stop) && $stop['status'] == 'A') {
            $isExtraTrain = 1;
        }
        $direction = $this->stationsRepository->getStationById($isArrivalBoard ? $stop['Origin1UicCode'] : $stop['Destination1UicCode']);
        $vehicle = Vehicle::fromTypeAndNumber($stop['CommercialType'], $stop['TrainNumber']);

        // Now all information has been parsed. Put it in a nice object.
        $stopAtStation = new DepartureOrArrival();
        $stopAtStation->setStation($currentStation);
        $stopAtStation->setVehicle($vehicle);
        $stopAtStation->setScheduledDateTime($plannedDateTime);
        $stopAtStation->setDelay($delay);
        $stopAtStation->setPlatform(new PlatformInfo(null, $platform, $isPlatformNormal));
        $stopAtStation->setIsCancelled($stopCanceled);
        $stopAtStation->setIsExtra($isExtraTrain);
        $stopAtStation->setIsReported($left);
        $stopAtStation->setDirection($direction);

        $stopAtStation->setOccupany($this->getOccupancy($currentStation, $vehicle, $plannedDateTime));
        return $stopAtStation;
    }

    /**
     * Parse the delay based on a string in hh:mm:ss format
     * @param string|null $delayString The delay string
     * @return int
     */
    private static function parseDelayInSeconds(?string $delayString): int
    {
        if ($delayString == null) {
            return 0;
        }
        sscanf($delayString, '%d:%d:%d', $hours, $minutes, $seconds);
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    /**
     * Add occupancy data (also known as spitsgids data) to the object.
     *
     * @param StationInfo  $currentStation
     * @param Vehicle  $vehicle
     * @param DateTime $date
     * @return Occupancy
     */
    private function getOccupancy(StationInfo $currentStation, Vehicle $vehicle, DateTime $date): Occupancy
    {
        // TODO: implement
        return new Occupancy();
    }
}
