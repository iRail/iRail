<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 *
 * This will fetch all vehicledata for the NMBS.
 */

namespace Irail\Repositories\Irail;

use DateTime;
use Exception;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Legacy\Occupancy\OccupancyOperations;
use Irail\Models\CachedData;
use Irail\Models\DepartureAndArrival;
use Irail\Models\DepartureOrArrival;
use Irail\Models\Result\VehicleJourneySearchResult;
use Irail\Models\Vehicle;
use Irail\Repositories\Irail\traits\BasedOnHafas;
use Irail\Repositories\Nmbs\Models\hafas\HafasResponseContext;
use Irail\Repositories\Nmbs\Models\Stop;
use Irail\Repositories\Nmbs\StationsRepository;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Irail\Repositories\VehicleJourneyRepository;
use stdClass;

class NmbsRivVehicleRepository implements VehicleJourneyRepository
{
    use  BasedOnHafas;

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
     * @param VehicleJourneyRequest $request
     * @return VehicleJourneySearchResult
     * @throws Exception
     */
    public function getDatedVehicleJourney(VehicleJourneyRequest $request): VehicleJourneySearchResult
    {
        $rawData = $this->rivDataRepository->getVehicleJourneyData($request);
        $this->stationsRepository->setLocalizedLanguage($request->getLanguage());
        return $this->parseNmbsRawVehicleJourney($rawData);
    }

    /**
     * @param CachedData $cachedRawData
     * @return VehicleJourneySearchResult
     * @throws Exception
     */
    private function parseNmbsRawVehicleJourney(CachedData $cachedRawData): VehicleJourneySearchResult
    {
        $rawData = $cachedRawData->getValue();
        $json = $this->decodeAndVerifyResponse($rawData);

        $context = HafasResponseContext::fromJson($json);

        $vehicle = $this->parseVehicle($json, $context);
        $stops = $this->parseVehicleStops($json, $vehicle, $context);
        $alerts = []; // TODO: implement
        return new VehicleJourneySearchResult($cachedRawData->getCreatedAt(), $vehicle, $stops, $alerts);
    }


    /**
     * @param DateTime $requestedDate
     * @return bool
     */
    private static function isSpitsgidsDataAvailable(DateTime $requestedDate): bool
    {
        // Determine if this date is in the spitsgids range
        $now = new DateTime();
        $daysBetweenNowAndRequest = $now->diff($requestedDate);
        $isOccupancyDate = true;
        if ($daysBetweenNowAndRequest->d > 1 && $daysBetweenNowAndRequest->invert == 0) {
            $isOccupancyDate = false;
        }
        return $isOccupancyDate;
    }

    /**
     * @param DateTime $dateOfFirstDeparture The date when the train leaves the first station on its journey.
     * @param array    $occupancyArr Occuppancy data for this train
     * @param Stop     $stop The stop on which occuppancy data needs to be added.
     */
    protected static function addOccuppancyData(DateTime $dateOfFirstDeparture, $occupancyArr, Stop $stop): void
    {
        $isOccupancyDate = self::isSpitsgidsDataAvailable($dateOfFirstDeparture);
        // Check if it is in less than 2 days and MongoDB is available
        if ($isOccupancyDate && isset($occupancyArr)) {
            // Add occupancy
            $occupancyOfStationFound = false;
            $k = 0;

            while ($k < count($occupancyArr) && !$occupancyOfStationFound) {
                if ($stop->station->{'@id'} == $occupancyArr[$k]['from']) {
                    $occupancyURI = OccupancyOperations::NumberToURI($occupancyArr[$k]['occupancy']);
                    $stop->occupancy = new stdClass();
                    $stop->occupancy->{'@id'} = $occupancyURI;
                    $stop->occupancy->name = basename($occupancyURI);
                    $occupancyOfStationFound = true;
                }
                $k++;
            }

            if (!isset($stop->occupancy)) {
                $unknown = OccupancyOperations::getUnknown();
                $stop->occupancy = new stdClass();
                $stop->occupancy->{'@id'} = $unknown;
                $stop->occupancy->name = basename($unknown);
            }
        }
    }

    /**
     * @param DepartureAndArrival[] $stops
     */
    protected function ensureHasArrivedHasLeftConsistency(array $stops): void
    {
        for ($i = count($stops) - 1; $i > 0; $i--) {
            if ($stops[$i]->getArrival()->isReported()) {
                $stops[$i - 1]->getDeparture()?->setIsReported(true);
                $stops[$i - 1]->getArrival()?->setIsReported(true);
            }
        }
    }

    private function parseVehicle(array $json, HafasResponseContext $context): Vehicle
    {
        $vehicleIndex = $json['svcResL'][0]['res']['journey']['prodX'];
        return $this->getHafasVehicleAsIrailVehicle($context, $vehicleIndex);
    }

    /**
     * @param array                $json
     * @param HafasResponseContext $context
     * @return DepartureAndArrival[]
     * @throws Exception
     */
    private function parseVehicleStops(array $json, Vehicle $vehicle, HafasResponseContext $context): array
    {
        $stops = [];
        $firstDepartureDate = $json['svcResL'][0]['res']['journey']['date'];
        $firstDepartureDate = DateTime::createFromFormat('Ymd', $firstDepartureDate);

        $previousStop = null;
        // TODO: pick the right train here, a train which splits has multiple parts here.
        foreach ($json['svcResL'][0]['res']['journey']['stopL'] as $rawStop) {
            $stop = $this->parseVehicleStop(
                $rawStop,
                $firstDepartureDate,
                $context,
                $previousStop
            );

            $stops[] = $stop;
            $previousStop = $stop;
        }
        $this->ensureHasArrivedHasLeftConsistency($stops);

        return $stops;
    }

    /**
     * @param array                $rawStop
     * @param DateTime             $firstDeparture date on which the train leaves the first station on its journey.
     * @param HafasResponseContext $context
     * @return DepartureAndArrival
     * @throws Exception
     */
    private function parseVehicleStop(array $rawStop, DateTime $firstDeparture, HafasResponseContext $context, ?DepartureAndArrival $previousVehicleStop): DepartureAndArrival
    {
        $hafasStationId = $context->getLocation($rawStop['locX'])->getExtId();
        $iRailStationId = $this->hafasIdToIrailId($hafasStationId);
        $station = $this->stationsRepository->getStationById($iRailStationId);

        [$departureTime, $departureDelay] = $this->parseTimeAndDelay($rawStop, $firstDeparture, 'dTimeS', 'dTimeR');
        [$arrivalTime, $arrivalDelay] = $this->parseTimeAndDelay($rawStop, $firstDeparture, 'aTimeS', 'aTimeR');

        $departurePlatform = $this->parsePlatform($station, $rawStop, 'dPlatfS', 'dPlatfR');
        $arrivalPlatform = $this->parsePlatform($station, $rawStop, 'aPlatfS', 'aPlatfR');

        // Always fetch the vehicle from the product index to ensure we don't miss changes in train numbers
        $arrivalVehicle = key_exists('aProdX', $rawStop) ? $this->getHafasVehicleAsIrailVehicle($context, $rawStop['aProdX']) : null;
        $departureVehicle = key_exists('dProdX', $rawStop) ? $this->getHafasVehicleAsIrailVehicle($context, $rawStop['dProdX']) : null;

        $departureCanceled = 0;
        $arrivalCanceled = 0;

        [$left, $arrived] = $this->parseHasLeftHasArrived($rawStop);

        $arrivalHeadSign = $rawStop['aDirTxt'] ?? null;
        if ($arrivalHeadSign == null && $previousVehicleStop != null) {
            $arrivalHeadSign = $previousVehicleStop->getDeparture()->getHeadSign();
        }
        $departureHeadSign = $rawStop['dDirTxt'] ?? $arrivalHeadSign;

        // TODO: what does type mean?
        if (key_exists('dCncl', $rawStop)) {
            $departureCanceled = $rawStop['dCncl'];
        }

        if (key_exists('aCncl', $rawStop)) {
            $arrivalCanceled = $rawStop['aCncl'];
        }

        $stop = new DepartureAndArrival();
        if ($departureTime) {
            /** @noinspection DuplicatedCode */
            $departure = new DepartureOrArrival();
            $departure->setScheduledDateTime($departureTime)->setDelay($departureDelay);
            $departure->setPlatform($departurePlatform);
            $departure->setIsReported($left);
            $departure->setIsCancelled($departureCanceled);
            $departure->setHeadSign($departureHeadSign);
            $departure->setVehicle($departureVehicle);
            $departure->setStation($station);
            // TODO: detect somehow
            $departure->setIsExtra(false);
            $stop->setDeparture($departure);
        }

        if ($arrivalTime) {
            /** @noinspection DuplicatedCode */
            $arrival = new DepartureOrArrival();
            $arrival->setScheduledDateTime($arrivalTime)->setDelay($arrivalDelay);
            $arrival->setPlatform($arrivalPlatform);
            $arrival->setIsReported($arrived);
            $arrival->setIsCancelled($arrivalCanceled);
            $arrival->setHeadSign($arrivalHeadSign);
            $arrival->setVehicle($arrivalVehicle);
            $arrival->setStation($station);
            // TODO: detect somehow
            $arrival->setIsExtra(false);
            $stop->setArrival($arrival);
        }

        return $stop;
    }

    /**
     * @param array    $rawStop
     * @param DateTime $firstDeparture
     * @param string   $scheduledField the field containing the scheduled time (e.g. dTimeS)
     * @param string   $realtimeField the field containing the scheduled time (e.g. dTimeR)
     * @return array
     * @throws Exception
     */
    private function parseTimeAndDelay(array $rawStop, DateTime $firstDeparture, string $scheduledField, string $realtimeField): array
    {
        if (!key_exists($scheduledField, $rawStop)) {
            return [null, 0];
        }

        $scheduledTime = tools::parseDDHHMMSS($firstDeparture, $rawStop[$scheduledField]);

        if (key_exists($realtimeField, $rawStop)) {
            $delay = tools::calculateDDHHMMSSTimeDifferenceInSeconds(
                $firstDeparture,
                $rawStop[$scheduledField],
                $rawStop[$realtimeField],
            );
        } else {
            $delay = 0;
        }

        return [$scheduledTime, $delay];
    }

    /**
     * @param array $rawStop
     * @return int[]
     */
    private function parseHasLeftHasArrived(array $rawStop): array
    {
        $left = 0;
        if (key_exists('dProgType', $rawStop)) {
            if ($rawStop['dProgType'] == 'REPORTED') {
                $left = 1;
            }
        }

        if (key_exists('aProgType', $rawStop)) {
            if ($rawStop['aProgType'] == 'REPORTED') {
                $arrived = 1;
            } else {
                $arrived = 0;
            }
        } else {
            $arrived = 0;
        }

        // If the train left, it also arrived
        if ($left) {
            $arrived = 1;
        }
        return [$left, $arrived];
    }

    /**
     * @param HafasResponseContext $context
     * @param mixed                $vehicleIndex
     * @return Vehicle
     */
    private function getHafasVehicleAsIrailVehicle(HafasResponseContext $context, mixed $vehicleIndex): Vehicle
    {
        $hafasVehicle = $context->getVehicle($vehicleIndex);
        return new Vehicle(
            $hafasVehicle->getUri(),
            $hafasVehicle->getNumber(),
            $hafasVehicle->getType(),
            $hafasVehicle->getNumber());
    }

}
