<?php

namespace Irail\Repositories\Irail\traits;

use Exception;
use Irail\api\data\NMBS\tools\HafasCommon;
use Irail\api\data\NMBS\tools\Tools;
use Irail\Data\Nmbs\Models\Alert;
use Irail\Data\Nmbs\Repositories\Riv\HafasIntermediateStop;
use Irail\Data\Nmbs\Repositories\Riv\HafasVehicle;
use Irail\Data\Nmbs\Repositories\Riv\Platform;
use Irail\Data\Nmbs\Repositories\Riv\StationsDatasource;
use Irail\Data\Nmbs\Repositories\Riv\StdClass;
use Irail\Data\Nmbs\Repositories\Riv\VehicleInfo;
use Irail\Models\PlatformInfo;

trait BasedOnHafas
{

    /**
     * @param string $rawJsonData data to decode.
     * @return array an associative array representing the JSON response
     * @throws Exception thrown when the response is invalid or describes an error
     */
    protected function decodeAndVerifyResponse(string $rawJsonData): array
    {
        if (empty($rawJsonData)) {
            throw new Exception('The server did not return any data.', 500);
        }
        $json = json_decode($rawJsonData, true);
        $this->throwExceptionOnInvalidResponse($json);
        return $json;
    }

    /**
     * Throw an exception if the JSON API response contains an error instead of a result.
     *
     * @param array|null $json The JSON response as an associative array.
     *
     * @throws Exception An Exception containing an error message in case the JSON response contains an error message.
     */
    public static function throwExceptionOnInvalidResponse(?array $json): void
    {
        if ($json == null) {
            throw new Exception('This request failed due to internal errors.', 500);
        }

        if (!key_exists('errorCode', $json)) {
            // all ok!
            return;
        }

        if ($json['errorCode'] == 'INT_ERR'
            || $json['errorCode'] == 'INT_GATEWAY'
            || $json['errorCode'] == 'INT_TIMEOUT') {
            throw new Exception('NMBS data is temporarily unavailable.', 504);
        }
        if ($json['errorCode'] == 'SVC_NO_RESULT') {
            throw new Exception('No results found', 404);
        }
        if ($json['errorCode'] == 'SVC_DATATIME_PERIOD') {
            throw new Exception('Date  outside of the timetable period. Check your query.', 400);
        }
        throw new Exception('This request failed. Please check your query. Error code ' . $json['errorCode'], 500);
    }


    /**
     * Check whether or not the status of the arrival equals cancelled.
     *
     * @param string $status The status to check.
     *
     * @return bool True if the arrival is cancelled, or if the status has an unrecognized value.
     */
    public function isArrivalCanceledBasedOnState(string $status): bool
    {
        if ($status == 'SCHEDULED' ||
            $status == 'REPORTED' ||
            $status == 'PROGNOSED' ||
            $status == 'CALCULATED' ||
            $status == 'CORRECTED' ||
            $status == 'PARTIAL_FAILURE_AT_DEP') {
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
    public function isDepartureCanceledBasedOnState(string $status): bool
    {
        if ($status == 'SCHEDULED' ||
            $status == 'REPORTED' ||
            $status == 'PROGNOSED' ||
            $status == 'CALCULATED' ||
            $status == 'CORRECTED' ||
            $status == 'PARTIAL_FAILURE_AT_ARR') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $json
     *
     * @return array
     */
    public static function parseRemarkDefinitions($json): array
    {
        if (!key_exists('remL', $json['svcResL'][0]['res']['common'])) {
            return [];
        }

        $remarkDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['remL'] as $rawRemark) {
            /**
             *  "type": "I",
             * "code": "VIA",
             * "icoX": 5,
             * "txtN": "Opgelet: voor deze reis heb je 2 biljetten nodig.
             *          <a href=\"http:\/\/www.belgianrail.be\/nl\/klantendienst\/faq\/biljetten.aspx?cat=reisweg\">Meer info.<\/a>"
             */

            $remark = new StdClass();
            $remark->code = $rawRemark['code'];
            $remark->description = strip_tags(preg_replace(
                "/<a href=\".*?\">.*?<\/a>/",
                '',
                $rawRemark['txtN']
            ));

            $matches = [];
            preg_match_all("/<a href=\"(.*?)\">.*?<\/a>/", urldecode($rawRemark['txtN']), $matches);

            if (count($matches[1]) > 0) {
                $remark->link = urlencode($matches[1][0]);
            }

            $remarkDefinitions[] = $remark;
        }

        return $remarkDefinitions;
    }

    /**
     * Parse the list which contains information about all the alerts which are used in this API response.
     * Alerts warn about service interruptions etc.
     *
     * @param $json
     *
     * @return array
     * @throws Exception
     */
    public function parseAlerts($json): array
    {
        if (!key_exists('Messages', $json)) {
            return [];
        }

        $alertDefinitions = [];
        foreach ($json['Messages']['Message'] as $rawAlert) {
            /*
                              {
                  "affectedStops": {
                    "StopLocation": [
                      ...
                    ]
                  },
                  "validFromStop": {
                    "name": "Gent-Sint-Pieters",
                    "id": "A=1@O=Gent-Sint-Pieters@X=3710675@Y=51035897@U=80@L=8892007@",
                    "extId": "8892007",
                    "lon": 3.710675,
                    "lat": 51.035897
                  },
                  "validToStop": {
                    "name": "Antwerpen-Centraal",
                    "id": "A=1@O=Antwerpen-Centraal@X=4421102@Y=51217200@U=80@L=8821006@",
                    "extId": "8821006",
                    "lon": 4.421102,
                    "lat": 51.2172
                  },
                  "channel": [
                    ...
                  ],
                  "id": "66738",
                  "act": true,
                  "head": "Kortrijk - Deinze",
                  "lead": "We are conducting work for you between Kortrijk and Deinze.",
                  "text": "We are conducting work for you between Kortrijk and Deinze. Detailed information only available in French (FR) and in Dutch (NL).",
                  "company": "SNCB",
                  "category": "1",
                  "priority": 50,
                  "products": 57348,
                  "modTime": "11:45:57",
                  "modDate": "2022-10-17",
                  "icon": "HIM1",
                  "routeIdxFrom": 0,
                  "routeIdxTo": 14,
                  "sTime": "03:00:00",
                  "sDate": "2022-10-29",
                  "eTime": "23:59:00",
                  "eDate": "2022-11-06"
                }
              }*/

            $alert = new Alert();
            $alert->header = strip_tags($rawAlert['head']);
            $alert->description = strip_tags(preg_replace("/<a href=\".*?\">.*?<\/a>/", '', $rawAlert['text']));
            $alert->lead = strip_tags($rawAlert['lead']);

            preg_match_all("/<a href=\"(.*?)\">.*?<\/a>/", urldecode($rawAlert['text']), $matches);
            if (count($matches[1]) > 1) {
                $alert->link = urlencode($matches[1][0]);
            }
            $alert->startTime = $this->parseDateAndTime(
                $rawAlert['sTime'],
                $rawAlert['sDate']
            );
            $alert->endTime = $this->parseDateAndTime(
                $rawAlert['eTime'],
                $rawAlert['eDate']
            );
            $alertDefinitions[] = $alert;
        }
        return $alertDefinitions;
    }


    /**
     * Parse the arrival platform, and whether this is a normal platform or a changed one
     * @param array $departureOrArrival
     * @return PlatformInfo The platform for this departure.
     */
    public function parsePlatform(array $departureOrArrival): PlatformInfo
    {
        if (key_exists('depTrack', $departureOrArrival)) {
            return $this->parsePlatformFields($departureOrArrival, 'depTrack', 'rtDepTrack');
        }
        if (key_exists('arrTrack', $departureOrArrival)) {
            return $this->parsePlatformFields($departureOrArrival, 'arrTrack', 'rtArrTrack');
        }
        return $this->parsePlatformFields($departureOrArrival);
    }

    /**
     * @param array  $data The data object containing the platform information, for example a departure or arrival.
     * @param string $scheduledFieldName The name of the field containing information about the scheduled platform.
     * @param string $realTimeFieldName The name of the field containing information about the realtime platform.
     * @return PlatformInfo The platform for this departure/arrival.
     */
    private function parsePlatformFields(array $data, string $scheduledFieldName = 'track', string $realTimeFieldName = 'rtTrack'): PlatformInfo
    {
        if (key_exists($realTimeFieldName, $data)) {
            // Realtime correction exists
            return new PlatformInfo(null, $data[$realTimeFieldName], false);
        } else {
            if (key_exists($scheduledFieldName, $data)) {
                // Only scheduled data exists
                return new PlatformInfo(null, $data[$scheduledFieldName], true);
            } else {
                // No data
                return new PlatformInfo(null, '?', true);
            }
        }
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