<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 * This will fetch all vehicledata for the NMBS.
 *   * fillDataRoot will fill the entire dataroot with vehicleinformation
 */

namespace Irail\api\data\NMBS;

use DateTime;
use Exception;
use Irail\api\data\DataRoot;
use Irail\api\data\models\Alert;
use Irail\api\data\models\hafas\HafasVehicle;
use Irail\api\data\models\hafas\VehicleWithOriginAndDestination;
use Irail\api\data\models\Platform;
use Irail\api\data\models\Stop;
use Irail\api\data\models\VehicleInfo;
use Irail\api\data\NMBS\tools\GtfsTripStartEndExtractor;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\occupancy\OccupancyOperations;
use Irail\api\requests\VehicleinformationRequest;

class VehicleDatasource
{

    /**
     * @param DataRoot                  $dataroot
     * @param VehicleinformationRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, VehicleinformationRequest $request)
    {
        $lang = $request->getLang();
        $date = $request->getDate();

        $nmbsCacheKey = self::getNmbsCacheKey($request->getVehicleId(), $date, $lang);
        $serverData = Tools::getCachedObject($nmbsCacheKey);
        if ($serverData === false) {
            $serverData = self::getServerData($request->getVehicleId(), $date, $lang);
            Tools::setCachedObject($nmbsCacheKey, $serverData);
        }

        $vehicleOccupancy = OccupancyOperations::getOccupancy($request->getVehicleId(), $date);

        // Use this to check if the MongoDB module is set up. If not, the occupancy score will not be returned
        if (!is_null($vehicleOccupancy)) {
            $vehicleOccupancy = iterator_to_array($vehicleOccupancy);
        }

        $hafasVehicle = self::getVehicleDetails($serverData);
        $dataroot->vehicle = VehicleInfo::fromHafasVehicle($hafasVehicle);
        $dataroot->stop = self::getStops($serverData, $lang, $vehicleOccupancy, $dataroot->vehicle);

        $lastStop = $dataroot->stop[0];
        foreach ($dataroot->stop as $stop) {
            if ($stop->arrived) {
                $lastStop = $stop;
            }
        }

        if ($request->getAlerts() && self::getAlerts($serverData)) {
            $dataroot->alert = self::getAlerts($serverData);
        }

        if (!is_null($lastStop)) {
            $dataroot->vehicle->locationX = $lastStop->station->locationX;
            $dataroot->vehicle->locationY = $lastStop->station->locationY;
        }
    }

    public static function getNmbsCacheKey($id, $date, $lang)
    {
        return 'NMBSVehicle|' . join('.', [
                $id,
                $date,
                $lang,
            ]);
    }

    /**
     * @param String $vehicleName
     * @param String $date The date, in YYYYmmdd
     * @param String $lang
     * @return mixed
     * @throws Exception
     */
    private static function getServerData(string $vehicleName, string $date, string $lang): mixed
    {
        $vehicleWithOriginAndDestination = GtfsTripStartEndExtractor::getVehicleWithOriginAndDestination($vehicleName, $date);
        if ($vehicleWithOriginAndDestination === false) {
            throw new Exception("Vehicle not found", 404);
        }
        $url = "https://mobile-riv.api.belgianrail.be/riv/v1.0/journey";

        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $formattedDateStr = DateTime::createFromFormat('Ymd', $date)->format('Y-m-d');

        $parameters = [
            // 'trainFilter'      => 'S206466',// TODO: figure out valid values and meaning
            'trainFilter' => $vehicleName,
            'originExtId' => $vehicleWithOriginAndDestination->getOriginStopId(),
            'destExtId'   => $vehicleWithOriginAndDestination->getDestinationStopId(),
            'date'        => $formattedDateStr,
            'lang'        => $lang
        ];
        $url = $url . '?' . http_build_query($parameters, "", null,);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl']);

        $response = curl_exec($ch);

        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../../storage/debug-vehicle-' . $vehicleName . '-'
                . $vehicleWithOriginAndDestination->getOriginStopId() . '-'
                . $vehicleWithOriginAndDestination->getDestinationStopId() . '-' .
                time() . '.json',
                $response
            );
        }

        curl_close($ch);
        return $response;
    }

    /**
     * @param string $serverData
     * @param string $lang
     * @param        $occupancyArr
     * @return Stop[]
     * @throws Exception
     */
    protected static function getStops(string $serverData, string $lang, $occupancyArr, VehicleInfo $vehicle)
    {
        $json = json_decode($serverData, true);

        $stops = [];
        $stopIndex = 0;
        // TODO: pick the right train here, a train which splits has multiple parts here.
        $stopsList = $json['Trip'][0]['LegList']['Leg'][0]['Stops']['Stop'];
        foreach ($stopsList as $rawStop) {
            $stop = self::parseVehicleStop(
                $rawStop,
                $lang,
                $json['Trip'][0],
                $stopIndex == 0,
                $stopIndex == count($stopsList) - 1,
                $vehicle
            );

            $stops[] = $stop;
            self::addOccuppancyData($occupancyArr, $stop);
            $stopIndex++;
        }
        // Use ArrivalDelay instead of DepartureDelay for the last stop, since DepartureDelay will always be 0 (there is no departure)
        $stops[count($stops) - 1]->delay = $stops[count($stops) - 1]->arrivalDelay;

        self::ensureTrainHasLeftPreviousStop($stops);

        return $stops;
    }

    /**
     * @param          $rawStop
     * @param string   $lang
     * @param bool     $isFirstStop
     * @param bool     $isLastStop
     * @return Stop
     * @throws Exception
     */
    private static function parseVehicleStop(array $rawStop, string $lang, array $trip,
        bool $isFirstStop, bool $isLastStop, VehicleInfo $vehicle): Stop
    {
        // TODO: export remarks as they contain information about changes in the train designation.
        $hafasIntermediateStop = HafasCommon::parseHafasIntermediateStop(
            $lang,
            $rawStop,
            $trip
        );

        $stop = new Stop();
        $stop->station = $hafasIntermediateStop->station;

        $stop->scheduledDepartureTime = $hafasIntermediateStop->scheduledDepartureTime;
        $stop->departureDelay = $hafasIntermediateStop->departureDelay;
        $stop->departureCanceled = $hafasIntermediateStop->departureCanceled;

        $stop->scheduledArrivalTime = $hafasIntermediateStop->scheduledArrivalTime;
        $stop->arrivalDelay = $hafasIntermediateStop->arrivalDelay;
        $stop->arrivalCanceled = $hafasIntermediateStop->arrivalCanceled;

        $stop->platform = $hafasIntermediateStop->platform;

        $stop->time = $hafasIntermediateStop->scheduledDepartureTime;
        $stop->canceled = $hafasIntermediateStop->arrivalCanceled && $hafasIntermediateStop->departureCanceled;


        // The final doesn't have a departure product
        if ($isLastStop) {
            // Still include the field, just leave it empty.
            $stop->departureConnection = "";
        } else {
            $firstDepartureDate = str_replace('-', '', $trip['LegList']['Leg'][0]['Origin']['date']);
            $stop->departureConnection = 'http://irail.be/connections/' .
                substr(basename($stop->station->{'@id'}), 2) . '/' .
                $firstDepartureDate . '/' . $vehicle->name;
        }

        //for backward compatibility
        $stop->delay = $stop->departureDelay;

        // Arrived, left, extra
        $stop->arrived = $hafasIntermediateStop->arrived;
        $stop->left = $hafasIntermediateStop->left;
        $stop->isExtraStop = $hafasIntermediateStop->isExtraStop;
        return $stop;
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
     * @param string $serverData
     * @return HafasVehicle
     * @throws Exception
     */
    private static function getVehicleDetails(string $serverData): HafasVehicle
    {
        $json = json_decode($serverData, true);
        HafasCommon::throwExceptionOnInvalidResponse($json);
        return HafasCommon::parseProduct($json['Trip'][0]['LegList']['Leg'][0]['Product']);
    }

    /**
     * @param string $serverData
     * @return Alert[]
     */
    private static function getAlerts(string $serverData): array
    {
        $json = json_decode($serverData, true);
        // These are formatted already
        return HafasCommon::parseAlertDefinitions($json);
    }

    /**
     * @param DateTime $dateOfFirstDeparture The date when the train leaves the first station on its journey.
     * @param array    $occupancyArr Occuppancy data for this train
     * @param Stop     $stop The stop on which occuppancy data needs to be added.
     */
    protected static function addOccuppancyData($occupancyArr, Stop $stop): void
    {
        $isOccupancyDate = self::isSpitsgidsDataAvailable(new DateTime('@' . $stop->scheduledDepartureTime));
        // Check if it is in less than 2 days and MongoDB is available
        if ($isOccupancyDate && isset($occupancyArr)) {
            // Add occupancy
            $occupancyOfStationFound = false;
            $k = 0;

            while ($k < count($occupancyArr) && !$occupancyOfStationFound) {
                if ($stop->station->{'@id'} == $occupancyArr[$k]["from"]) {
                    $occupancyURI = OccupancyOperations::NumberToURI($occupancyArr[$k]["occupancy"]);
                    $stop->occupancy = new \stdClass();
                    $stop->occupancy->{'@id'} = $occupancyURI;
                    $stop->occupancy->name = basename($occupancyURI);
                    $occupancyOfStationFound = true;
                }
                $k++;
            }

            if (!isset($stop->occupancy)) {
                $unknown = OccupancyOperations::getUnknown();
                $stop->occupancy = new \stdClass();
                $stop->occupancy->{'@id'} = $unknown;
                $stop->occupancy->name = basename($unknown);
            }
        }
    }

    /**
     * @param array $stops
     */
    protected static function ensureTrainHasLeftPreviousStop(array $stops): void
    {
        for ($i = count($stops) - 1; $i > 0; $i--) {
            if ($stops[$i]->arrived == 1) {
                $stops[$i - 1]->arrived = 1;
                $stops[$i - 1]->left = 1;
            }
        }
        // The first stop can't have arrived == 1, since there is no arrival.
        $stops[0]->arrived = 0;
    }

}
