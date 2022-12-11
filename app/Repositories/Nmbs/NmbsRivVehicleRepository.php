<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 *
 * This will fetch all vehicledata for the NMBS.
 */

namespace Irail\Repositories\Nmbs;

use Exception;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Exceptions\Internal\UnknownStopException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Http\Requests\VehicleJourneyRequest;
use Irail\Models\CachedData;
use Irail\Models\DepartureAndArrival;
use Irail\Models\Message;
use Irail\Models\Result\VehicleJourneySearchResult;
use Irail\Models\Vehicle;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\Traits\BasedOnHafas;
use Irail\Repositories\Nmbs\Traits\TimeParser;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Irail\Repositories\VehicleJourneyRepository;

class NmbsRivVehicleRepository implements VehicleJourneyRepository
{
    use BasedOnHafas;
    use TimeParser;

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
        return $this->parseNmbsRawVehicleJourney($request, $rawData);
    }

    /**
     * @param VehicleJourneyRequest $request
     * @param CachedData            $cachedRawData
     * @return VehicleJourneySearchResult
     * @throws InternalProcessingException
     * @throws UnknownStopException
     * @throws UpstreamServerException
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
                $lang,
                $rawStop,
                $vehicle,
            );
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
        $vehicle = $this->parseProduct($json['Names']['Name'][0]['Product']);
        return $vehicle->toVehicle();
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

}
