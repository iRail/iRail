<?php

class HafasCommon
{

    /**
     * Throw an exception if the JSON API response contains an error instead of a result.
     * @param array $json The JSON response as an associative array.
     * @throws Exception An Exception containing an error message in case the JSON response contains an error message.
     */
    public static function throwExceptionOnInvalidResponse(array $json): void
    {
        if ($json['svcResL'][0]['err'] == "H9360") {
            throw new Exception("Date outside of the timetable period.", 404);
        }
        if ($json['svcResL'][0]['err'] == "H890") {
            throw new Exception('No results found', 404);
        }
        if ($json['svcResL'][0]['err'] != 'OK') {
            throw new Exception("We're sorry, this data is not available from our sources at this moment. Error code " . $json['svcResL'][0]['err'], 500);
        }
    }

    /**
     * Check whether or not the status of the arrival equals cancelled.
     * @param string $status The status to check.
     * @return bool True if the arrival is cancelled, or if the status has an unrecognized value.
     */
    public static function isArrivalCanceledBasedOnState(string $status): bool
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
     * @param string $status The status to check.
     * @return bool True if the departure is cancelled, or if the status has an unrecognized value.
     */
    public static function isDepartureCanceledBasedOnState(string $status): bool
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

}