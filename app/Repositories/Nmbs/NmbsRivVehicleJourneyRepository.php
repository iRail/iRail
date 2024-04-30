<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 *
 * This will fetch all vehicledata for the NMBS.
 */

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Irail\Database\OccupancyDao;
use Irail\Exceptions\Internal\GtfsVehicleNotFoundException;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Exceptions\NoResultsException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\CachedData;
use Irail\Models\DepartureAndArrival;
use Irail\Models\Message;
use Irail\Models\Result\VehicleJourneySearchResult;
use Irail\Models\Vehicle;
use Irail\Models\VehicleDirection;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\Traits\BasedOnHafas;
use Irail\Repositories\Nmbs\Traits\TimeParser;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Irail\Repositories\VehicleJourneyRepository;

class NmbsRivVehicleJourneyRepository implements VehicleJourneyRepository
{
    use BasedOnHafas;
    use TimeParser;

    private StationsRepository $stationsRepository;
    private NmbsRivRawDataRepository $rivDataRepository;
    private OccupancyDao $occupancyRepository;

    public function __construct(StationsRepository $stationsRepository, NmbsRivRawDataRepository $rivDataRepository)
    {
        $this->stationsRepository = $stationsRepository;
        $this->rivDataRepository = $rivDataRepository;
        $this->occupancyRepository = App::make(OccupancyDao::class);
    }

    /**
     * This is the entry point for the data fetching and transformation.
     *
     * @param VehicleJourneyRequest $request
     * @return VehicleJourneySearchResult
     * @throws GtfsVehicleNotFoundException
     */
    public function getDatedVehicleJourney(VehicleJourneyRequest $request): VehicleJourneySearchResult
    {
        $rawData = $this->rivDataRepository->getVehicleJourneyData($request);
        return $this->parseNmbsRawVehicleJourney($request, $rawData);
    }

    /**
     * @param VehicleJourneyRequest $request
     * @param CachedData            $cachedRawData
     * @return VehicleJourneySearchResult
     * @throws InternalProcessingException
     * @throws UnknownStopException
     * @throws UpstreamServerException
     * @throws NoResultsException
     */
    private function parseNmbsRawVehicleJourney(VehicleJourneyRequest $request, CachedData $cachedRawData): VehicleJourneySearchResult
    {
        $rawData = $cachedRawData->getValue();
        $json = $this->deserializeAndVerifyResponse($rawData);

        $vehicle = $this->getVehicleDetails($json);
        $stops = $this->parseVehicleStops($json, $vehicle, $request->getLanguage());
        $alerts = $this->getAlerts($json);

        $vehicleJourneySearchResult = new VehicleJourneySearchResult($vehicle, $stops, $alerts);
        $vehicleJourneySearchResult->mergeCacheValidity($cachedRawData->getCreatedAt(), $cachedRawData->getExpiresAt());
        return $vehicleJourneySearchResult;
    }

    /**
     * @param array   $json
     * @param Vehicle $vehicle
     * @param string  $lang
     * @return DepartureAndArrival[]
     * @throws InternalProcessingException
     * @throws UnknownStopException
     */
    protected function parseVehicleStops(array $json, Vehicle $vehicle, string $lang): array
    {
        $stops = [];
        $stopsList = $json['Stops']['Stop'];
        foreach ($stopsList as $rawStop) {
            $stop = $this->parseHafasIntermediateStop(
                $this->stationsRepository,
                $rawStop,
                $vehicle,
            );
            if ($stop->getDeparture()) {
                // The last stop does not have a departure
                $stop->getDeparture()->setOccupancy($this->occupancyRepository->getOccupancy($stop->getDeparture()));
            }
            $stops[] = $stop;
        }
        $this->fixInconsistentReportedStates($stops);
        return $stops;
    }


    /**
     * @param array $json
     * @return Vehicle
     */
    private function getVehicleDetails(array $json): Vehicle
    {
        $hafasVehicle = $this->parseProduct($json['Names']['Name'][0]['Product']);

        // Source does not contain leading zeroes
        $journeyStartDateStr = str_pad(explode('|', $json['ref'])[4], 8, '0', STR_PAD_LEFT);
        $journeyStartDate = Carbon::createFromFormat('dmY', $journeyStartDateStr, 'Europe/Stockholm');
        $vehicle = $hafasVehicle->toVehicle($journeyStartDate);
        $this->setDirection($vehicle, $json);
        return $vehicle;
    }

    /**
     * @param array $json
     * @return Message[]
     * @throws InternalProcessingException
     */
    private function getAlerts(array $json): array
    {
        if (key_exists('Messages', $json)) {
            return $this->parseAlerts($json['Messages']['Message']);
        } else {
            return [];
        }
    }

    /**
     * @param Vehicle $vehicle
     * @param array   $json
     * @return void
     */
    private function setDirection(Vehicle $vehicle, array $json): void
    {
        $directionData = $json['Directions']['Direction'][0];
        $destinationStation = end($json['Stops']['Stop']);
        if ($destinationStation['routeIdx'] != $directionData['routeIdxTo']) {
            // Assume the destination station is the last one, as this always seems the case. Throw an exception if this is wrong so it can be fixed.
            throw new InternalProcessingException(
                500,
                'Unexpected destination station with idx ' . $directionData['routeIdxTo'] . ' was not found at the end of the stations list'
            );
        }

        if (key_exists('value', $directionData)) {
            $vehicle->setDirection(
                new VehicleDirection(
                    $directionData['value'],
                    $this->stationsRepository->getStationByHafasId($destinationStation['extId'])
                )
            );
        } else {
            $vehicle->setDirection(
                new VehicleDirection(
                    $destinationStation['name'],
                    $this->stationsRepository->getStationByHafasId($destinationStation['extId'])
                )
            );
        }
    }
}
