<?php

namespace Irail\Data\Nmbs;

use Exception;
use Irail\Data\Nmbs\Models\Alert;
use Irail\Data\Nmbs\Models\hafas\HafasInformationManagerMessage;
use Irail\Data\Nmbs\Models\hafas\HafasLocationDefinition;
use Irail\Data\Nmbs\Models\hafas\HafasRemark;
use Irail\Data\Nmbs\Models\hafas\HafasVehicle;
use Irail\Models\PlatformInfo;
use Irail\Models\StationInfo;

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
     * @param StationInfo $parentStation
     * @param array       $rawStop
     * @param string      $schedPlatformField
     * @param string      $rtPlatformField
     * @return PlatformInfo|null
     */
    protected function parsePlatform(StationInfo $parentStation, array $rawStop, string $schedPlatformField, string $rtPlatformField): ?PlatformInfo
    {
        if (key_exists($rtPlatformField, $rawStop)) {
            $platformDesignation = $rawStop[$rtPlatformField];
            $isScheduledPlatform = false;
        } else {
            if (key_exists($schedPlatformField, $rawStop)) {
                $platformDesignation = $rawStop[$schedPlatformField];
                $isScheduledPlatform = true;
            } else {
                return null;
            }
        }
        return new PlatformInfo($parentStation->getId(), $platformDesignation, $isScheduledPlatform);
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