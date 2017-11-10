<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */
include_once 'data/NMBS/tools.php';
include_once 'data/NMBS/stations.php';
include_once 'occupancy/OccupancyOperations.php';

class liveboard
{
    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot, $request)
    {
        $stationr = $request->getStation();
        $dataroot->station = new \stdClass();

        try {
            $dataroot->station = stations::getStationFromName($stationr, strtolower($request->getLang()));
        } catch (Exception $e) {
            throw new Exception('Could not find station ' . $stationr, 404);
        }
        $request->setStation($dataroot->station);

        if (strtoupper(substr($request->getArrdep(), 0, 1)) == 'A') {
            $nmbsCacheKey = self::getNmbsCacheKey($dataroot->station, $request->getTime(), $request->getDate(),
                $request->getLang(), 'arr');
            $html = Tools::getCachedObject($nmbsCacheKey);

            if ($html === false) {
                $html = self::fetchData($dataroot->station, $request->getTime(), $request->getDate(),
                    $request->getLang(), 'arr');
                Tools::setCachedObject($nmbsCacheKey, $html);
            }

            $dataroot->arrival = self::parseData($html, $request->getTime(), $request->getLang(), $request->isFast(),
                $request->getAlerts(), null, $request->getFormat());
        } elseif (strtoupper(substr($request->getArrdep(), 0, 1)) == 'D') {
            $nmbsCacheKey = self::getNmbsCacheKey($dataroot->station, $request->getTime(), $request->getDate(),
                $request->getLang(), 'dep');
            $html = Tools::getCachedObject($nmbsCacheKey);

            if ($html === false) {
                $html = self::fetchData($dataroot->station, $request->getTime(), $request->getDate(),
                    $request->getLang(), 'dep');
                Tools::setCachedObject($nmbsCacheKey, $html);
            }

            $dataroot->departure = self::parseData($html, $request->getTime(), $request->getLang(), $request->isFast(),
                $request->getAlerts(), $dataroot->station, $request->getFormat());
        } else {
            throw new Exception('Not a good timeSel value: try ARR or DEP', 400);
        }
    }

    public static function getNmbsCacheKey($station, $time, $date, $lang, $timeSel)
    {
        return 'NMBSLiveboard|' . join('.', [
                $station->id,
                str_replace(':', '.', $time),
                $date,
                $timeSel,
                $lang,
            ]);
    }

    /**
     * @param $station
     * @param $time
     * @param $lang
     * @param $timeSel
     * @return string
     */
    private static function fetchData($station, $time, $date, $lang, $timeSel)
    {
        include '../includes/getUA.php';
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => $irailAgent,
        ];

        $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";

        $postdata = '{
  "auth": {
    "aid": "sncb-mobi",
    "type": "AID"
  },
  "client": {
    "id": "SNCB",
    "name": "NMBS",
    "os": "Android 5.0.2",
    "type": "AND",
    "ua": "SNCB\/302132 (Android_5.0.2) Dalvik\/2.1.0 (Linux; U; Android 5.0.2; HTC One Build\/LRX22G)",
    "v": 302132
  },
 "lang":"' . $lang . '",
  "svcReqL": [
    {
      "cfg": {
        "polyEnc": "GPA"
      },
      "meth": "StationBoard",
      "req": {
        "date": "' . $date . '",
        "jnyFltrL": [
          {
            "mode": "BIT",
            "type": "PROD",
            "value": "11101111000111"
          }
        ],
        "stbLoc": {
          "lid": "A=1@O=' . $station->name . '@U=80@L=00' . $station->getHID() . '@B=1@p=1429490515@",
          "name": "' . $station->name . '",
          "type": "S"
        },
        "time": "' . str_replace(':', '', $time) . '00",
        "getPasslist": false,
        "getTrainComposition": false,
        "maxJny": 50
      }
    }
  ],
  "ver": "1.11",
  "formatted": false
}';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * @param      $serverData
     * @param      $time
     * @param      $lang
     * @param bool $fast
     * @param bool $showAlerts
     * @return array
     */
    private static function parseData($serverData, $time, $lang, $fast = false, $showAlerts = false, $station, $format)
    {
        $json = json_decode($serverData, true);

        if ($json['svcResL'][0]['err'] == "H9360") {
            throw new Exception("Date outside of the timetable period.", 404);
        }
        if ($json['svcResL'][0]['err'] != 'OK') {
            throw new Exception("We're sorry, we could not parse the correct data from our sources", 500);
        }

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

        $alertDefinitions = [];
        if (key_exists('himL', $json['svcResL'][0]['res']['common'])) {
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
                    $alert->startTime = Tools::transformTime($rawAlert['pubChL'][0]['fTime'],
                        $rawAlert['pubChL'][0]['fDate']);
                    $alert->endTime = Tools::transformTime($rawAlert['pubChL'][0]['tTime'],
                        $rawAlert['pubChL'][0]['tDate']);
                }

                $alertDefinitions[] = $alert;
            }
        }

        $departureIndex = 0;
        $nodes = [];
        $departureStation = Stations::getStationFromID($locationDefinitions[0]->id, $lang);
        foreach ($json['svcResL'][0]['res']['jnyL'] as $departure) {
            /*
             *   "jid": "1|586|1|80|26102017",
                                    "date": "20171026",
                                    "prodX": 0,
                                    "dirTxt": "Eeklo",
                                    "status": "P",
                                    "isRchbl": true,
                                    "stbStop": {
                                        "locX": 0,
                                        "idx": 7,
                                        "dProdX": 0,
                                        "dPlatfS": "2",
                                        "dPlatfR": "1",
                                        "dTimeS": "141200",
                                        "dTimeR": "141200",
                                        "dProgType": "PROGNOSED"
                                    }
             */

            $date = $departure['date'];
            if (key_exists('dTimeR', $departure['stbStop'])) {
                $delay = tools::calculateSecondsHHMMSS($departure['stbStop']['dTimeR'],
                    $date, $departure['stbStop']['dTimeS'], $date);
            } else {
                $delay = 0;
            }
            $unixtime = tools::transformTime($departure['stbStop']['dTimeS'], $date);

            $vehicle = $vehicleDefinitions[ $departure['stbStop']['dProdX']];

            //Delay and platform changes
            if (key_exists('dPlatfR', $departure['stbStop'])) {
                $departurePlatform =  $departure['stbStop']['dPlatfR'];
                $departurePlatformNormal = false;
            } elseif (key_exists('dPlatfS', $departure['stbStop'])) {
                $departurePlatform =  $departure['stbStop']['dPlatfS'];
                $departurePlatformNormal = true;
            } else {
                // TODO: is this what we want when we don't know the platform?
                $departurePlatform = "?";
                $departurePlatformNormal = true;
            }

            // Canceled means the entire train is canceled, partiallyCanceled means only a few stops are canceled.
            // DepartureCanceled gives information if this stop has been canceled.
            $canceled = 0;
            $partiallyCanceled = 0;
            $departureCanceled = 0;
            if (key_exists('isCncl', $departure)) {
                $canceled = $departure['isCncl'];
            }
            if (key_exists('isPartCncl', $departure)) {
                $partiallyCanceled = $departure['isPartCncl'];
            }
            if ($canceled) {
                $partiallyCanceled = 1; // Completely canceled is a special case of partially canceled
            }

            $left = 0;
            if (key_exists('dProgType', $departure['stbStop'])) {
                if ($departure['stbStop']['dProgType'] == 'REPORTED') {
                    $left = 1;
                }
                if (key_exists('dCncl', $departure['stbStop'])) {
                    $departureCanceled =  $departure['stbStop']['dCncl'];
                }
            }


            $station = stations::getStationFromName($departure['dirTxt'], $lang);

            $nodes[$departureIndex] = new DepartureArrival();
            $nodes[$departureIndex]->delay = $delay;
            $nodes[$departureIndex]->station = $station;
            $nodes[$departureIndex]->time = $unixtime;
            $nodes[$departureIndex]->vehicle = new \stdClass();
            $nodes[$departureIndex]->vehicle->name = 'BE.NMBS.' . $vehicle->name;
            $nodes[$departureIndex]->vehicle->{'@id'} = 'http://irail.be/vehicle/' . $vehicle->name;
            $nodes[$departureIndex]->platform = new Platform();
            $nodes[$departureIndex]->platform->name = $departurePlatform;
            $nodes[$departureIndex]->platform->normal = $departurePlatformNormal;
            $nodes[$departureIndex]->canceled = $departureCanceled;
            // Include partiallyCanceled, but don't include canceled.
            // PartiallyCanceled might mean the next 3 stations are canceled while this station isn't.
            // Canceled means all stations are canceled, including this one
            // TODO: enable partially canceled as soon as it's reliable, ATM it still marks trains which aren't partially canceled at all
            // $nodes[$departureIndex]->partiallyCanceled = $partiallyCanceled;
            $nodes[$departureIndex]->left = $left;
            $nodes[$departureIndex]->departureConnection = 'http://irail.be/connections/' . substr(basename($departureStation->{'@id'}),
                    2) . '/' . date('Ymd', $unixtime) . '/' . $vehicle->name;

            if (! is_null($departureStation)) {
                try {
                    $occupancy = OccupancyOperations::getOccupancyURI($nodes[$departureIndex]->vehicle->{'@id'}, $departureStation->{'@id'}, $date);

                    // Check if the MongoDB module is set up. If not, the occupancy score will not be returned.
                    if (! is_null($occupancy)) {
                        $nodes[$departureIndex]->occupancy = new \stdClass();
                        $nodes[$departureIndex]->occupancy->name = basename($occupancy);
                        $nodes[$departureIndex]->occupancy->{'@id'} = $occupancy;
                    }
                } catch (Exception $e) {
                    // Database connection failed, in the future a warning could be given to the owner of iRail
                    $departureStation = null;
                }
            }

            $departureIndex++;
        }

        return array_merge($nodes); //array merge reindexes the array
    }
}

;
