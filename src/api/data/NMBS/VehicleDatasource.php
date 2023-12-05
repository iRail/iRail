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
use Irail\api\data\models\Stop;
use Irail\api\data\models\VehicleInfo;
use Irail\api\data\NMBS\tools\GtfsTripStartEndExtractor;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\api\occupancy\OccupancyOperations;
use Irail\api\requests\VehicleinformationRequest;

class VehicleDatasource
{
    const API_KEY = 'IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl';

    /**
     * @param DataRoot                  $dataroot
     * @param VehicleinformationRequest $request
     * @throws Exception
     */
    public static function fillDataRoot(DataRoot $dataroot, VehicleinformationRequest $request)
    {
        $lang = $request->getLang();
        $date = $request->getDate();

        $vehicleName = $request->getVehicleId();
        $vehicleName = str_replace('BE.NMBS.', '', $vehicleName);
        $nmbsCacheKey = self::getNmbsCacheKey($vehicleName, $date, $lang);
        $serverData = Tools::getCachedObject($nmbsCacheKey);
        if ($serverData === false) {
            $serverData = self::getServerData($vehicleName, $date, $lang);
            Tools::setCachedObject($nmbsCacheKey, $serverData);
        }

        $vehicleOccupancy = OccupancyOperations::getOccupancy($vehicleName, $date);

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
     * Make multiple calls to get the best data.
     *
     * Step 1. Obtain the origin and destination station for a train from the GTFS data
     * Step 2. Use data from step 1 to obtain a journey reference of the form 1|4256|0|80|16112022
     * Step 3. Use the journey reference to get detailed information including the platforms
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
            throw new Exception("Vehicle not found in GTFS data", 404);
        }
        $journeyDetailRef = self::getJourneyDetailRef($date, $vehicleName, $vehicleWithOriginAndDestination, $lang);
        if ($journeyDetailRef === null) {
            throw new Exception("Vehicle not found", 404);
        }
        return self::getJourneyDetailResponse($journeyDetailRef, $lang, $vehicleName, $vehicleWithOriginAndDestination);
    }

    /**
     * @param String                          $date
     * @param String                          $vehicleName
     * @param VehicleWithOriginAndDestination $vehicleWithOriginAndDestination
     * @param String                          $lang
     * @return string|null
     */
    private static function getJourneyDetailRef(string $date, string $vehicleName, VehicleWithOriginAndDestination $vehicleWithOriginAndDestination, string $lang): ?string
    {
        $journeyDetailRef = self::findVehicleJourneyRefBetweenStops($vehicleWithOriginAndDestination, $date, $lang);
        # If false, check if it has been partially cancelled.
        if ($journeyDetailRef === false) {
            $journeyDetailRef = self::getJourneyDetailRefAlt($vehicleWithOriginAndDestination, $vehicleName, $date, $lang);
        }
        # If still false, fail
        if ($journeyDetailRef === false) {
            throw new Exception("Vehicle not found", 404);
        }
        return $journeyDetailRef;
    }

    /**
     * Get the journey detail reference by trying alternative origin-destination stretches, to cope with cancelled origin/destination stops
     * @param VehicleWithOriginAndDestination $vehicleWithOriginAndDestination
     * @param String                          $vehicleName
     * @param String                          $date
     * @param String                          $lang
     * @return string|bool
     * @throws Exception
     */
    public static function getJourneyDetailRefAlt(VehicleWithOriginAndDestination $vehicleWithOriginAndDestination, string $vehicleName, string $date, string $lang): string|bool
    {
        $journeyRef = Tools::getCachedObject("gtfs|getJourneyDetailRefAlt|{$vehicleWithOriginAndDestination->getVehicleNumber()}");
        if ($journeyRef !== false) {
            return $journeyRef;
        }
        $alternativeOriginDestinations = GtfsTripStartEndExtractor::getAlternativeVehicleWithOriginAndDestination(
            $vehicleWithOriginAndDestination
        );
        // Assume the first and last stop are cancelled, since the normal origin-destination search did not return results
        // This saves 2 requests and should not make a difference.
        $i = 1;
        while ($journeyRef === false && $i < count($alternativeOriginDestinations) - 1) {
            # error_log("Searching for vehicle $vehicleName using alternative segments, $i");
            $altVehicleWithOriginAndDestination = $alternativeOriginDestinations[$i++];
            $journeyRef = self::findVehicleJourneyRefBetweenStops($altVehicleWithOriginAndDestination, $date, $lang);
        }
        # Cache for 4 hours
        Tools::setCachedObject("gtfs|getJourneyDetailRefAlt|{$vehicleWithOriginAndDestination->getVehicleNumber()}",$journeyRef,14400);
        return $journeyRef;
    }

    /**
     * @param VehicleWithOriginAndDestination $vehicleWithOriginAndDestination
     * @param string                          $date
     * @param String                          $lang
     * @return string|false
     */
    public static function findVehicleJourneyRefBetweenStops( VehicleWithOriginAndDestination $vehicleWithOriginAndDestination, string $date, string $lang): string|false
    {
        $url = "https://mobile-riv.api.belgianrail.be/riv/v1.0/journey";

        $formattedDateStr = DateTime::createFromFormat('Ymd', $date)->format('Y-m-d');

        $vehicleName = $vehicleWithOriginAndDestination->getVehicleType() . $vehicleWithOriginAndDestination->getVehicleNumber();
        $parameters = [
            'trainFilter' => $vehicleName, // type + number, type is required!
            'originExtId' => $vehicleWithOriginAndDestination->getOriginStopId(),
            'destExtId'   => $vehicleWithOriginAndDestination->getDestinationStopId(),
            'date'        => $formattedDateStr,
            'lang'        => $lang
        ];
        $url = $url . '?' . http_build_query($parameters, "", null,);

        $journeyResponse = self::makeNmbsRequest($url);
        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../../storage/debug-vehicle-' . time() . '-' . $vehicleName . '-'
                . $vehicleWithOriginAndDestination->getOriginStopId() . '-'
                . $vehicleWithOriginAndDestination->getDestinationStopId() . '-journey.json',
                $journeyResponse
            );
        }
        $journeyResponse = json_decode($journeyResponse, true);
        if (!key_exists('Trip', $journeyResponse)) {
            return false;
        }
        return $journeyResponse['Trip'][0]['LegList']['Leg'][0]['JourneyDetailRef']['ref'];
    }

    /**
     * @param string                          $journeyDetailRef
     * @param String                          $lang
     * @param String                          $vehicleName
     * @param VehicleWithOriginAndDestination $vehicleWithOriginAndDestination
     * @return bool|string
     */
    private static function getJourneyDetailResponse(string $journeyDetailRef, string $lang, string $vehicleName, VehicleWithOriginAndDestination $vehicleWithOriginAndDestination): string|bool
    {
        $url = "https://mobile-riv.api.belgianrail.be/riv/v1.0/journey/detail";
        $parameters = [
            'id'   => $journeyDetailRef,
            'lang' => $lang
        ];
        $url = $url . '?' . http_build_query($parameters, "", null,);

        $journeyResponse = self::makeNmbsRequest($url);
        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents(
                '../../storage/debug-vehicle-' . time() . '-' . $vehicleName . '-'
                . $vehicleWithOriginAndDestination->getOriginStopId() . '-'
                . $vehicleWithOriginAndDestination->getDestinationStopId() . '-journeyDetail.json',
                $journeyResponse
            );
        }
        return $journeyResponse;
    }


    /**
     * @param string $url
     * @return bool|string
     */
    private static function makeNmbsRequest(string $url): string|bool
    {
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . getenv('NMBS_API_KEY') ?? self::API_KEY]);

        $response = curl_exec($ch);

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
        $stopsList = $json['Stops']['Stop'];
        foreach ($stopsList as $rawStop) {
            $stop = self::parseVehicleStop(
                $rawStop,
                $lang,
                $vehicle,
                $stopsList[0]['depDate']
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
     * @param array       $rawStop
     * @param string      $lang
     * @param VehicleInfo $vehicle
     * @param string      $departureDate departure date from first stop in yyyy-mm-dd format
     * @return Stop
     * @throws Exception
     */
    private static function parseVehicleStop(array $rawStop, string $lang, VehicleInfo $vehicle, string $departureDate): Stop
    {
        // TODO: export remarks as they contain information about changes in the train designation.
        $hafasIntermediateStop = HafasCommon::parseHafasIntermediateStop(
            $lang,
            $rawStop,
            $vehicle,
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
        if (!key_exists('depTime', $rawStop)) {
            // Still include the field, just leave it empty.
            $stop->departureConnection = "";
        } else {
            $firstDepartureDate = str_replace('-', '', $departureDate);
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
        $vehicle = HafasCommon::parseProduct($json['Names']['Name'][0]['Product']);
        return $vehicle;
    }

    /**
     * @param string $serverData
     * @return Alert[]
     */
    private static function getAlerts(string $serverData): array
    {
        $json = json_decode($serverData, true);
        if (key_exists('Messages', $json)) {
            return HafasCommon::parseAlertDefinitions($json['Messages']['Message']);
        } else {
            return [];
        }
    }

    /**
     * @param array $occupancyArr Occuppancy data for this train
     * @param Stop  $stop The stop on which occuppancy data needs to be added.
     * @throws Exception
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
