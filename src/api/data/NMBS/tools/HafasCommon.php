<?php

namespace Irail\api\data\NMBS\tools;

use stdClass;

/**
 * This class offers utility methods to work with HaCon/HAFAS data, which is used by the NMBS/SNCB.
 */
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

    /**
     * @param $json
     * @return array
     */
    public static function parseRemarkDefinitions($json): array
    {
        $remarkDefinitions = [];
        if (key_exists('remL', $json['svcResL'][0]['res']['common'])) {
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
                $remark->description = strip_tags(preg_replace("/<a href=\".*?\">.*?<\/a>/", '',
                    $rawRemark['txtN']));

                $matches = [];
                preg_match_all("/<a href=\"(.*?)\">.*?<\/a>/", urldecode($rawRemark['txtN']), $matches);

                if (count($matches[1]) > 0) {
                    $remark->link = urlencode($matches[1][0]);
                }

                $remarkDefinitions[] = $remark;
            }
        }
        return $remarkDefinitions;
    }

    /**
     * Parse the list which contains information about all the alerts which are used in this API response.
     * Alerts warn about service interruptions etc.
     *
     * @param $json
     * @return array
     */
    public static function parseAlertDefinitions($json): array
    {
        $alertDefinitions = [];
        foreach ($json['svcResL'][0]['res']['common']['himL'] as $rawAlert) {
            /*
                "hid": "23499",
                "type": "LOC",
                "act": true,
                "head": "S Gravenbrakel: Wisselstoring.",
                "lead": "Wisselstoring.",
                "text": "Vertraagd verkeer.<br \/><br \/> Vertragingen tussen 5 en 10 minuten zijn mogelijk.<br \/><br \/> Dienst op enkel spoor tussen Tubeke en S Gravenbrakel.",
                "icoX": 3,
                "prio": 25,
                "prod": 1893,
                "pubChL": [
                  {
                      "name": "timetable",
                    "fDate": "20171016",
                    "fTime": "082000",
                    "tDate": "20171018",
                    "tTime": "235900"
                  }
                ]
              }*/

            $alert = new StdClass();
            $alert->header = strip_tags($rawAlert['head']);
            $alert->description = strip_tags(preg_replace("/<a href=\".*?\">.*?<\/a>/", '', $rawAlert['text']));
            $alert->lead = strip_tags($rawAlert['lead']);

            preg_match_all("/<a href=\"(.*?)\">.*?<\/a>/", urldecode($rawAlert['text']), $matches);
            if (count($matches[1]) > 1) {
                $alert->link = urlencode($matches[1][0]);
            }

            if (key_exists('pubChL', $rawAlert)) {
                $alert->startTime = Tools::transformTime(
                    $rawAlert['pubChL'][0]['fTime'],
                    $rawAlert['pubChL'][0]['fDate']
                );
                $alert->endTime = Tools::transformTime(
                    $rawAlert['pubChL'][0]['tTime'],
                    $rawAlert['pubChL'][0]['tDate']
                );
            }

            $alertDefinitions[] = $alert;
        }
        return $alertDefinitions;
    }
    /**
     * @param $json
     * @return array
     */
    public static function parseVehicleDefinitions($json): array
    {
        $vehicleDefinitions = [];
        if (key_exists('prodL', $json['svcResL'][0]['res']['common'])) {
            foreach ($json['svcResL'][0]['res']['common']['prodL'] as $rawTrain) {
                /*
                     {
                       "name": "IC 545",
                       "number": "545",
                       "icoX": 3,
                       "cls": 4,
                       "prodCtx": {
                         "name": "IC   545",
                         "num": "545",
                         "catOut": "IC      ",
                         "catOutS": "007",
                         "catOutL": "IC ",
                         "catIn": "007",
                         "catCode": "2",
                         "admin": "88____"
                       }
                     },
                 */

                $vehicle = new StdClass();
                $vehicle->name = str_replace(" ", '', $rawTrain['name']);
                $vehicle->num = trim($rawTrain['prodCtx']['num']);
                $vehicle->category = trim($rawTrain['prodCtx']['catOut']);
                $vehicleDefinitions[] = $vehicle;
            }
        }
        return $vehicleDefinitions;
    }

    /**
     * @param $json
     * @return array
     */
    public static function parseLocationDefinitions($json): array
    {
        $locationDefinitions = [];
        if (key_exists('remL', $json['svcResL'][0]['res']['common'])) {
            foreach ($json['svcResL'][0]['res']['common']['locL'] as $rawLocation) {
                /*
                  {
                      "lid": "A=1@O=Namur@X=4862220@Y=50468794@U=80@L=8863008@",
                      "type": "S",
                      "name": "Namur",
                      "icoX": 1,
                      "extId": "8863008",
                      "crd": {
                        "x": 4862220,
                        "y": 50468794
                      },
                      "pCls": 100,
                      "rRefL": [
                        0
                      ]
                    }
                 */

                // S stand for station, P for Point of Interest, A for address

                $location = new StdClass();
                $location->name = $rawLocation['name'];
                $location->id = '00' . $rawLocation['extId'];
                $locationDefinitions[] = $location;
            }
        }
        return $locationDefinitions;
    }

}
