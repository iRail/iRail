<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 */

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\App;
use Irail\Database\OccupancyDao;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Exceptions\Request\RequestOutsideTimetableRangeException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Http\Requests\LiveboardRequest;
use Irail\Http\Requests\TimeSelection;
use Irail\Models\CachedData;
use Irail\Models\DepartureArrivalState;
use Irail\Models\DepartureOrArrival;
use Irail\Models\PlatformInfo;
use Irail\Models\Result\LiveboardSearchResult;
use Irail\Models\StationInfo;
use Irail\Models\Vehicle;
use Irail\Models\VehicleDirection;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;

class NmbsRivLiveboardRepository implements LiveboardRepository
{
    private StationsRepository $stationsRepository;
    private NmbsRivRawDataRepository $rivDataRepository;
    private GtfsTripStartEndExtractor $gtfsTripStartEndExtractor;
    private OccupancyDao $occupancyRepository;

    public function __construct(
        StationsRepository $stationsRepository,
        GtfsTripStartEndExtractor $gtfsTripStartEndExtractor,
        NmbsRivRawDataRepository $rivDataRepository
    ) {
        $this->stationsRepository = $stationsRepository;
        $this->gtfsTripStartEndExtractor = $gtfsTripStartEndExtractor;

        $this->rivDataRepository = $rivDataRepository;
        $this->occupancyRepository = App::make(OccupancyDao::class);
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

    /**
     * @throws UnknownStopException
     */
    private function parseNmbsRawData(LiveboardRequest $request, CachedData $cachedRawData): LiveboardSearchResult
    {
        $rawData = $cachedRawData->getValue();

        if (empty($rawData)) {
            throw new UpstreamServerException('The server did not return any data.', 500);
        }
        if (str_contains($rawData, ': error :')) {
            throw new UpstreamServerException('The remote server returned an error: ' . $rawData, 504);
        }

        // Now we'll actually read the departures/arrivals information.
        $currentStation = $this->stationsRepository->getStationById($request->getStationId());

        $decodedJsonData = json_decode($rawData, associative: true);
        $stopsAtStation = $decodedJsonData['entries'];
        $departuresOrArrivals = [];
        foreach ($stopsAtStation as $stop) {
            if ($this->isServiceTrain($stop)) {
                // Service trains head to workplaces, such as Vorst-rijtuigen.
                // Since they probably won't be available any longer as soon as we switch to any other data source,
                // don't include them in the responses. They also refer to stations closed for passengers such as
                // 008817327, which are not included in the GTFS data.
                continue;
            }

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
     * @throws UnknownStopException | RequestOutsideTimetableRangeException | UpstreamServerException
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
        $plannedDateTime = Carbon::createFromFormat('Y-m-d H:i:s',
            $isArrivalBoard ? $stop['PlannedArrival'] : $stop['PlannedDeparture'],
            'Europe/Brussels'
        );

        $delay = self::parseDelayInSeconds($stop, $isArrivalBoard ? 'ArrivalDelay' : 'DepartureDelay');

        // parse the scheduled time of arrival/departure and the vehicle (which is returned as a number to look up in the vehicle definitions list)
        // $hafasVehicle] = self::parseScheduledTimeAndVehicle($stop, $date, $vehicleDefinitions);
        // parse information about which platform this train will depart from/arrive to.
        $platform = key_exists('Platform', $stop) ? $stop['Platform'] : '?';
        $hasPlatformChanged = key_exists('PlatformChanged', $stop) && $stop['PlatformChanged'] == 1;

        // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
        // DepartureCanceled gives information if this stop has been canceled.
        $stopCanceled = key_exists('Status', $stop) && $stop['Status'] == 'Canceled';

        $status = null;
        $statusMap = [
            'aan perron' => DepartureArrivalState::HALTING,
        ];
        if (key_exists('DepartureStatusNl', $stop) && key_exists($stop['DepartureStatusNl'], $statusMap)) {
            $status = $statusMap[$stop['DepartureStatusNl']];
        }

        // using plannedDateTime as trip start date is not 100% correct here, but we don't have anything better. Might cause issues on trains crossing midnight.
        $direction = $this->getDirectionUicCode($stop, $isArrivalBoard, $plannedDateTime);
        $direction = $this->stationsRepository->getStationById('00' . $direction);
        $headSign = $request->getDepartureArrivalMode() == TimeSelection::DEPARTURE ?
            $stop['DestinationNl'] : $stop['OriginNl'];
        $vehicle = Vehicle::fromTypeAndNumber($stop['CommercialType'], $stop['TrainNumber']);
        $vehicle->setDirection(new VehicleDirection($headSign, $direction));

        // Now all information has been parsed. Put it in a nice object.
        $stopAtStation = new DepartureOrArrival();
        $stopAtStation->setStation($currentStation);
        $stopAtStation->setVehicle($vehicle);
        $stopAtStation->setScheduledDateTime($plannedDateTime);
        $stopAtStation->setDelay($delay);
        $stopAtStation->setPlatform(new PlatformInfo($currentStation->getId(), $platform, $hasPlatformChanged));
        $stopAtStation->setIsCancelled($stopCanceled);
        $stopAtStation->setStatus($status);
        $stopAtStation->setIsExtra(key_exists('status', $stop) && $stop['status'] == 'A');
        $stopAtStation->setOccupancy($this->occupancyRepository->getOccupancy($stopAtStation));
        return $stopAtStation;
    }

    /**
     * Parse the delay based on a string in hh:mm:ss format
     * @param array  $stop The raw stop data array
     * @param string $arrayKey The key pointing to the delay value
     * @return int
     */
    private static function parseDelayInSeconds(array $stop, string $arrayKey): int
    {
        if (!key_exists($arrayKey, $stop)) {
            return 0;
        }
        sscanf($stop[$arrayKey], '%d:%d:%d', $hours, $minutes, $seconds);
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    private function isServiceTrain(array $stop): bool
    {
        return $stop['CommercialType'] == 'SERV';
    }

    /**
     * @throws UpstreamServerException | RequestOutsideTimetableRangeException
     */
    private function getDirectionUicCode(array $stop, bool $isArrivalBoard, DateTime $scheduledTripDepartureDate)
    {
        if ($isArrivalBoard) {
            if (key_exists('Origin1UicCode', $stop)) {
                return $stop['Origin1UicCode'];
            }

            // GTFS to the rescue in the case of a missing origin!
            return $this->gtfsTripStartEndExtractor->getVehicleWithOriginAndDestination(
                $stop['TrainNumber'],
                $scheduledTripDepartureDate
            )->getOriginStopId();
        }
        if (key_exists('Destination1UicCode', $stop)) {
            return $stop['Destination1UicCode'];
        }
        // GTFS to the rescue in the case of a missing destination!
        return $this->gtfsTripStartEndExtractor->getVehicleWithOriginAndDestination(
            $stop['TrainNumber'],
            $scheduledTripDepartureDate
        )->getDestinationStopId();
    }
}
