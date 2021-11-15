<?php

namespace Irail\Data\Nmbs;

use Exception;
use Irail\Data\Nmbs\Models\Alert;
use Irail\Data\Nmbs\Models\hafas\HafasInformationManagerMessage;
use Irail\Data\Nmbs\Models\hafas\HafasLocationDefinition;
use Irail\Data\Nmbs\Models\hafas\HafasRemark;
use Irail\Data\Nmbs\Models\hafas\HafasVehicle;

trait HafasDatasource
{

    /**
     * @param string $rawJsonData data to decode.
     * @return array an associative array representing the JSON response
     * @throws Exception thrown when the response is invalid or describes an error
     */
    protected function decodeAndVerifyResponse(string $rawJsonData): array
    {
        if (empty($rawJsonData)) {
            throw new Exception("The server did not return any data.", 500);
        }
        $json = json_decode($rawJsonData, true);
        $this->throwExceptionOnInvalidResponse($json);
        return $json;
    }

    /**
     * Throw an exception if the JSON API response contains an error instead of a result.
     *
     * @param array $json The JSON response as an associative array.
     *
     * @throws Exception An Exception containing an error message in case the JSON response contains an error message.
     */
    protected function throwExceptionOnInvalidResponse(array $json): void
    {
        if ($json['svcResL'][0]['err'] == "H9360") {
            throw new Exception("Date outside of the timetable period.", 404);
        }
        if ($json['svcResL'][0]['err'] == "H890") {
            throw new Exception('No results found', 404);
        }
        if ($json['svcResL'][0]['err'] == 'PROBLEMS') {
            throw new Exception("Date likely outside of the timetable period. Check your query.", 400);
        }
        if ($json['svcResL'][0]['err'] != 'OK') {
            throw new Exception("We're sorry, this data is not available from our sources at this moment. Error code " . $json['svcResL'][0]['err'], 500);
        }
    }

    /**
     * Check whether the status of the arrival equals cancelled.
     *
     * @param string $status The status to check.
     *
     * @return bool True if the arrival is cancelled, or if the status has an unrecognized value.
     */
    protected function isArrivalCanceledBasedOnState(string $status): bool
    {
        if ($status == "SCHEDULED" ||
            $status == "REPORTED" ||
            $status == "PROGNOSED" ||
            $status == "CALCULATED" ||
            $status == "CORRECTED" ||
            $status == "PARTIAL_FAILURE_AT_DEP") {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check whether or not the status of the departure equals cancelled.
     *
     * @param string $status The status to check.
     *
     * @return bool True if the departure is cancelled, or if the status has an unrecognized value.
     */
    protected function isDepartureCanceledBasedOnState(string $status): bool
    {
        if ($status == "SCHEDULED" ||
            $status == "REPORTED" ||
            $status == "PROGNOSED" ||
            $status == "CALCULATED" ||
            $status == "CORRECTED" ||
            $status == "PARTIAL_FAILURE_AT_ARR") {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $json
     *
     * @return HafasRemark[]
     */
    protected function parseRemarkDefinitions($json): array
    {
        if (!key_exists('remL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $remarkDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['remL'] as $rawRemark) {
            $remarkType = $rawRemark['type'];
            $remarkCode = $rawRemark['code'];
            $remarkText = strip_tags(preg_replace(
                "/<a href=\".*?\">.*?<\/a>/",
                '',
                $rawRemark['txtN']
            ));
            $remarkDefinitions[] = new HafasRemark($remarkType, $remarkCode, $remarkText);
        }

        return $remarkDefinitions;
    }

    /**
     * Parse the list which contains information about all the service messages which are used in this API response.
     * Service messages warn about service interruptions etc.
     *
     * @param $json
     *
     * @return HafasInformationManagerMessage[]
     */
    protected function parseInformationMessageDefinitions($json): array
    {
        if (!key_exists('himL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $alertDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['himL'] as $rawAlert) {
            $startDate = \DateTime::createFromFormat("Ymd His", $rawAlert['sDate'] . ' ' . $rawAlert['sTime']);
            $endDate = \DateTime::createFromFormat("Ymd His", $rawAlert['eDate'] . ' ' . $rawAlert['eTime']);
            $modDate = \DateTime::createFromFormat("Ymd His", $rawAlert['lModDate'] . ' ' . $rawAlert['lModTime']);
            $header = $rawAlert['head'];
            $message = $rawAlert['text'];
            $lead = $rawAlert['lead'];
            $publisher = strip_tags($rawAlert['comp']);
            $message = new HafasInformationManagerMessage($startDate, $endDate, $modDate, $header, $lead, $message, $publisher);
            $alertDefinitions[] = $message;
        }
        return $alertDefinitions;
    }

    /**
     * @param $json
     *
     * @return HafasVehicle[]
     */
    protected function parseVehicleDefinitions($json): array
    {
        if (!key_exists('prodL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $vehicleDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['prodL'] as $rawTrain) {
            $vehicleDisplayName = str_replace(" ", '', $rawTrain['name']);
            $vehicleNumber = trim($rawTrain['number']);
            if (key_exists('prodCtx', $rawTrain)) {
                $vehicleType = trim($rawTrain['prodCtx']['catOutL']);
            } else {
                $vehicleType = trim(str_replace($vehicleNumber, '', $vehicleDisplayName));
            }
            $vehicleDefinitions[] = new HafasVehicle($vehicleNumber, $vehicleDisplayName, $vehicleType);
        }

        return $vehicleDefinitions;
    }

    /**
     * @param $json
     *
     * @return HafasLocationDefinition[]
     */
    protected function parseLocationDefinitions($json): array
    {
        if (!key_exists('locL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $locationDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['locL'] as $index => $rawLocation) {
            $location = new HafasLocationDefinition($index, $rawLocation['name'], $rawLocation['extId']);
            $locationDefinitions[] = $location;
        }

        return $locationDefinitions;
    }

    protected function iRailToHafasId(string $iRailStationId)
    {
        return substr($iRailStationId, 2);
    }


    protected function hafasIdToIrailId(string $hafasStationId)
    {
        return '00' . $hafasStationId;
    }
}