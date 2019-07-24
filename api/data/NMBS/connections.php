<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 * This will return information about 1 specific route for the NMBS.
 *
 * fillDataRoot will fill the entire dataroot with connections
 */
include_once 'data/NMBS/tools.php';
include_once 'data/NMBS/stations.php';
include_once 'occupancy/OccupancyOperations.php';

class connections
{
    /**
     * @param $dataroot
     * @param $request
     */
    const TYPE_TRANSPORT_ALL = '10101110111';
    const TYPE_TRANSPORT_ONLY_TRAINS = '1010111';
    const TYPE_TRANSPORT_ONLY_TRAINS_NO_INTERNATIONAL_TRAINS = '0010111';

    public static function fillDataRoot($dataroot, $request)
    {
        $from = $request->getFrom();
        if (count(explode('.', $request->getFrom())) > 1) {
            $from = stations::getStationFromID($request->getFrom(), $request->getLang());
            $from = $from->name;
        }
        $to = $request->getTo();
        if (count(explode('.', $request->getTo())) > 1) {
            $to = stations::getStationFromID($request->getTo(), $request->getLang());
            $to = $to->name;
        }
        $dataroot->connection = self::scrapeConnections($from, $to, $request->getTime(), $request->getDate(), $request->getResults(), $request->getLang(), $request->getFast(), $request->getAlerts(), $request->getTimeSel(), $request->getTypeOfTransport(), $request);
    }

    /**
     * @param string    $from
     * @param string    $to
     * @param           $time
     * @param           $date
     * @param           $results
     * @param           $lang
     * @param           $fast
     * @param bool      $showAlerts
     * @param string    $timeSel
     * @param string    $typeOfTransport
     * @return array
     * @throws Exception
     */
    private static function scrapeConnections($from, $to, $time, $date, $results, $lang, $fast, $showAlerts, $timeSel = 'depart', $typeOfTransport = 'trains', $request)
    {
        // TODO: clean the whole station name/id to object flow
        $stations = self::getStationsFromName($from, $to, $lang, $request);

        $nmbsCacheKey = self::getNmbsCacheKey($stations[0]->hafasId, $stations[1]->hafasId, $lang, $time, $date, $results, $timeSel,
            $typeOfTransport);

        $xml = Tools::getCachedObject($nmbsCacheKey);
        if ($xml === false) {
            $xml = self::requestHafasXml($stations[0], $stations[1], $lang, $time, $date, $results, $timeSel, $typeOfTransport);
            Tools::setCachedObject($nmbsCacheKey, $xml);
        }

        $connections = self::parseConnectionsAPI($xml, $lang, $fast, $request, $showAlerts, $request->getFormat());

        $requestedDate = DateTime::createFromFormat('Ymd', $date);
        $now = new DateTime();
        $daysDiff = $now->diff($requestedDate);

        if (intval($daysDiff->format('%R%a')) >= 2) {
            return $connections;
        } else {
            return self::addOccupancy($connections, $date);
        }
    }

    public static function getNmbsCacheKey($idfrom, $idto, $lang, $time, $date, $results, $timeSel, $typeOfTransport)
    {
        return 'NMBSConnections|' . join('.', [
                $idfrom,
                $idto,
                $lang,
                str_replace(':', '.', $time),
                $date,
                $timeSel,
                $results,
                $typeOfTransport,
            ]);
    }

    /**
     * This function converts 2 station names into two stations, which are returned as an array
     *
     * @param string $from
     * @param string $to
     * @param        $lang
     * @return array
     * @throws Exception
     */
    private static function getStationsFromName($from, $to, $lang, $request)
    {
        try {
            $station1 = stations::getStationFromName($from, $lang);
            $station2 = stations::getStationFromName($to, $lang);

            if (isset($request)) {
                $request->setFrom($station1);
                $request->setTo($station2);
            }
            return [$station1, $station2];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 404);
        }
    }

    /**
     * @param Station $stationFrom
     * @param Station $stationTo
     * @param string  $lang
     * @param         $time
     * @param         $date
     * @param int     $results
     * @param int     $timeSel
     * @param string  $typeOfTransport
     * @return string
     */
    private static function requestHafasXml($stationFrom, $stationTo, $lang, $time, $date, $results, $timeSel, $typeOfTransport)
    {
        include '../includes/getUA.php';
        $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";
        // OLD URL: $url = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/extxml.exe';
        // OLDER URL: $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";

        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => $irailAgent,
        ];

        // Automatic is the default type, which prevents that local trains aren't shown because a high-speed train provides a faster connection
        if ($typeOfTransport == 'automatic') {
            // 2 national stations: no international trains
            // Internation station: all
            if (strpos($stationFrom->hafasId, '0088') === 0 && strpos($stationTo->hafasId, '0088') === 0) {
                $typeOfTransportCode = self::TYPE_TRANSPORT_ONLY_TRAINS_NO_INTERNATIONAL_TRAINS;
            } else {
                $typeOfTransportCode = self::TYPE_TRANSPORT_ONLY_TRAINS;
            }
        } else if ($typeOfTransport == 'nointernationaltrains') {
            $typeOfTransportCode = self::TYPE_TRANSPORT_ONLY_TRAINS_NO_INTERNATIONAL_TRAINS;
        } else if ($typeOfTransport == 'trains') {
            $typeOfTransportCode = self::TYPE_TRANSPORT_ONLY_TRAINS;
        } else if ($typeOfTransport == 'all') {
            $typeOfTransportCode = self::TYPE_TRANSPORT_ALL;
        } else {
            // Only trains is the default
            $typeOfTransportCode = self::TYPE_TRANSPORT_ONLY_TRAINS;
        }


        if (strpos($timeSel, 'dep') === 0) {
            $timeSel = 0;
        } else {
            $timeSel = 1;
        }

        // numF: number of results: server-side capped to 5, but ask 10 in case they'd let us
        $postdata = [
            'auth'      => [
                'aid'  => 'sncb-mobi',
                'type' => 'AID'
            ],
            'client'    => [
                'id'   => 'SNCB',
                'name' => 'NMBS',
                'os'   => 'Android 5.0.2', 'type' => 'AND', 'ua' => '', 'v' => 302132
            ],
            // Response language (for station names)
            'lang'      => $lang,
            'svcReqL'   => [
                [
                    'cfg'  => [
                        'polyEnc' => 'GPA'
                    ],
                    // Route query
                    'meth' => 'TripSearch',
                    'req'  => [

                        // TODO: include as many parameters as possible in locations to prevent future issues
                        // Official Location ID (lid): "A=1@O=Zaventem@X=4469886@Y=50885723@U=80@L=008811221@B=1@p=1518483428@n=ac.1=GA@"
                        // "eteId": "A=1@O=Zaventem@X=4469886@Y=50885723@U=80@L=008811221@B=1@p=1518483428@n=ac.1=GA@Zaventem",
                        // "extId": "8811221",

                        // Departure station
                        'depLocL'  => [
                            [
                                'lid' => 'L=' . $stationFrom->hafasId . '@A=1@B=1@U=80@p=1481329402@n=ac.1=GA@', 'type' => 'S', 'extId' => substr($stationFrom->hafasId, 2)
                            ]
                        ],

                        // Arrival station
                        'arrLocL'  => [
                            [
                                'lid' => 'L=' . $stationTo->hafasId . '@A=1@B=1@U=80@p=1533166603@n=ac.1=GI@', 'type' => 'S', 'extId' => substr($stationTo->hafasId, 2)
                            ]
                        ],

                        // Transport type filters
                        'jnyFltrL' => [['mode' => 'BIT', 'type' => 'PROD', 'value' => $typeOfTransportCode]],
                        // Search date
                        'outDate'  => $date,
                        // Search time
                        'outTime'  => str_replace(':', '', $time) . '00',

                        'economic'    => false,
                        'extChgTime'  => -1,
                        'getIST'      => false,
                        // Intermediate stops
                        'getPasslist' => true,
                        // Coordinates of a line visualizing the trip (direct lines between stations, doesn't show the tracks)
                        'getPolyline' => false,
                        // Number of results
                        'numF'        => 10,
                        'liveSearch'  => false
                    ]
                ]
            ],
            'ver'       => '1.11',
            // Don't pretty print json replies (costs time and bandwidth)
            'formatted' => false
        ];

        // search by arrival time instead of by departure time
        if ($timeSel == 1) {
            $postdata['svcReqL'][0]['req']['outFrwd'] = false;
        }

        $postdata = json_encode($postdata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);

        $response = curl_exec($ch);

        // Store the raw output to a file on disk, for debug purposes
        if (key_exists('debug', $_GET) && isset($_GET['debug'])) {
            file_put_contents('../storage/debug-connections-' . $stationFrom->hafasId . '-' . $stationTo->hafasId . '-' . time() . '.log',
                $response);
        }

        curl_close($ch);
        return $response;
    }

    /**
     * @param string   $serverData
     * @param string   $lang
     * @param          $fast
     * @param          $request
     * @param bool     $showAlerts
     * @param          $format
     * @return array
     * @throws Exception
     */
    public static function parseConnectionsAPI($serverData, $lang, $fast, $request, $showAlerts = false, $format)
    {
        $json = json_decode($serverData, true);

        if ($json['svcResL'][0]['err'] == "H9360") {
            throw new Exception("Date outside of the timetable period.", 404);
        }
        if ($json['svcResL'][0]['err'] == "H890") {
            throw new Exception('No results found', 404);
        }
        if ($json['svcResL'][0]['err'] != 'OK') {
            throw new Exception("We're sorry, this data is not available from our sources at this moment", 500);
        }
        $connection = [];
        $journeyoptions = [];
        $i = 0;
        if ($json['svcResL'][0]['err'] == 'OK') {
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

            $departureStation = Stations::getStationFromID($locationDefinitions[0]->id, $lang);

            foreach ($json['svcResL'][0]['res']['outConL'] as $conn) {

                /*
                 *  "cid": "C-0",
                        "date": "20171010",
                        "dur": "005300",
                        "chg": 1,
                        "sDays": {
                            "sDaysR": "niet dagelijks",
                            "sDaysI": "10. Okt t/m 8. Dec 2017 Ma - Vr; niet 1. Nov 2017",
                            "sDaysB": "F9F3E6CF9F3E7CF800000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000"
                        },
                        "dep": {
                            "locX": 0,
                            "dPlatfS": "4",
                            "dTimeS": "192700",
                            "dProgType": "PROGNOSED"
                        },
                        "arr": {
                            "locX": 1,
                            "aPlatfS": "12",
                            "aTimeS": "202000",
                            "aProgType": "PROGNOSED"
                        },
                        "secL": [
                            {
                                "type": "JNY",
                                "icoX": 2,
                                "dep": {
                                    "locX": 0,
                                    "dPlatfS": "4",
                                    "dTimeS": "192700",
                                    "dProgType": "PROGNOSED"
                                },
                                "arr": {
                                    "locX": 2,
                                    "aPlatfS": "12",
                                    "aTimeS": "193600",
                                    "aProgType": "PROGNOSED"
                                },
                                "jny": {
                                    "jid": "1|972|0|80|10102017",
                                    "prodX": 0,
                                    "dirTxt": "Luik-Guillemins",
                                    "status": "P",
                                    "isRchbl": true,
                                    "stopL": [
                                        {
                                            "locX": 0,
                                            "idx": 18,
                                            "aTimeS": "192600",
                                            "dProdX": 0,
                                            "dTimeS": "192700"
                                        },
                                        {
                                            "locX": 2,
                                            "idx": 23,
                                            "aProdX": 0,
                                            "aTimeS": "193600",
                                            "dTimeS": "193900"
                                        }
                                    ],
                                    "ctxRecon": "T$A=1@O=Halle@L=8814308@a=128@$A=1@O=Brussel-Zuid@L=8814001@a=128@$201710101927$201710101936$IC  1718$"
                                }
                            },
                            {
                                "type": "JNY",
                                "icoX": 2,
                                "dep": {
                                    "locX": 2,
                                    "dPlatfS": "17",
                                    "dTimeS": "195100",
                                    "dProgType": "PROGNOSED"
                                },
                                "arr": {
                                    "locX": 1,
                                    "aPlatfS": "12",
                                    "aTimeS": "202000",
                                    "aProgType": "PROGNOSED"
                                },
                                "jny": {
                                    "jid": "1|851|0|80|10102017",
                                    "prodX": 1,
                                    "dirTxt": "Blankenberge",
                                    "status": "P",
                                    "isRchbl": true,
                                    "stopL": [
                                        {
                                            "locX": 2,
                                            "idx": 25,
                                            "aTimeS": "194800",
                                            "dProdX": 1,
                                            "dTimeS": "195100"
                                        },
                                        {
                                            "locX": 1,
                                            "idx": 26,
                                            "aProdX": 1,
                                            "aTimeS": "202000",
                                            "dTimeS": "202400"
                                        }
                                    ],
                                    "ctxRecon": "T$A=1@O=Brussel-Zuid@L=8814001@a=128@$A=1@O=Gent-Sint-Pieters@L=8892007@a=128@$201710101951$201710102020$IC  1541$"
                                }
                            }
                        ],
                        "ctxRecon": "T$A=1@O=Halle@L=8814308@a=128@$A=1@O=Brussel-Zuid@L=8814001@a=128@$201710101927$201710101936$IC  1718$§T$A=1@O=Brussel-Zuid@L=8814001@a=128@$A=1@O=Gent-Sint-Pieters@L=8892007@a=128@$201710101951$201710102020$IC  1541$",
                        "conSubscr": "U"
                    },
                ...
                 */
                $connection[$i] = new Connection();
                $connection[$i]->duration = tools::transformDurationHHMMSS($conn['dur']);

                $connection[$i]->departure = new DepartureArrival();
                $connection[$i]->departure->station = $departureStation;

                // When a train has been cancelled mid-run, the arrival station can be different than the planned one!
                // Therefore, always parse it from the planner results
                $arrivalStation = Stations::getStationFromID($locationDefinitions[$conn['arr']['locX']]->id, $lang);


                if (key_exists('dTimeR', $conn['dep'])) {
                    $connection[$i]->departure->delay = tools::calculateSecondsHHMMSS($conn['dep']['dTimeR'],
                        $conn['date'], $conn['dep']['dTimeS'], $conn['date']);
                } else {
                    $connection[$i]->departure->delay = 0;
                }
                $connection[$i]->departure->time = tools::transformTime($conn['dep']['dTimeS'], $conn['date']);

                //Delay and platform changes
                if (key_exists('dPlatfR', $conn['dep'])) {
                    $departurePlatform = $conn['dep']['dPlatfR'];
                    $departurePlatformNormal = false;
                } else if (key_exists('dPlatfS', $conn['dep'])) {
                    $departurePlatform = $conn['dep']['dPlatfS'];
                    $departurePlatformNormal = true;
                } else {
                    // TODO: is this what we want when we don't know the platform?
                    $departurePlatform = "?";
                    $departurePlatformNormal = true;
                }


                $connection[$i]->arrival = new DepartureArrival();
                $connection[$i]->arrival->station = $arrivalStation;

                if (key_exists('aTimeR', $conn['arr'])) {
                    $connection[$i]->arrival->delay = Tools::calculateSecondsHHMMSS($conn['arr']['aTimeR'],
                        $conn['date'], $conn['arr']['aTimeS'], $conn['date']);
                } else {
                    $connection[$i]->arrival->delay = 0;
                }

                $connection[$i]->arrival->time = tools::transformTime($conn['arr']['aTimeS'], $conn['date']);

                //Delay and platform changes
                if (key_exists('aPlatfR', $conn['arr'])) {
                    $arrivalPlatform = $conn['arr']['aPlatfR'];
                    $arrivalPlatformNormal = false;
                } else if (key_exists('aPlatfS', $conn['arr'])) {
                    $arrivalPlatform = $conn['arr']['aPlatfS'];
                    $arrivalPlatformNormal = true;
                } else {
                    // TODO: is this what we want when we don't know the platform?
                    $arrivalPlatform = "?";
                    $arrivalPlatformNormal = true;
                }

                $connection[$i]->departure->platform = new Platform();
                $connection[$i]->departure->platform->name = $departurePlatform;
                $connection[$i]->departure->platform->normal = $departurePlatformNormal;

                $connection[$i]->arrival->platform = new Platform();
                $connection[$i]->arrival->platform->name = $arrivalPlatform;
                $connection[$i]->arrival->platform->normal = $arrivalPlatformNormal;

                $trains = [];
                $vias = [];

                $trainIndex = 0;

                // For the sake of readability: the response contains trains, not vias. Therefore, just parse the trains, and create via's based on the trains later.
                // This is way more readable compared to instantly creating the vias
                // Loop over all train rides in the list. This will also include the first train ride.
                foreach ($conn['secL'] as $trainRide) {
                    if ($trainRide['dep']['locX'] == $trainRide['arr']['locX']) {
                        // Don't parse a train ride from station X to that same station X.
                        // NMBS/SNCB likes to include this utterly useless information to clutter their UI.
                        continue;
                    }

                    $departTime = tools::transformTime($trainRide['dep']['dTimeS'], $conn['date']);

                    if (key_exists('dPlatfR', $trainRide['dep'])) {
                        $departPlatform = $trainRide['dep']['dPlatfR'];
                        $departPlatformNormal = false;
                    } else if (key_exists('dPlatfS', $trainRide['dep'])) {
                        $departPlatform = $trainRide['dep']['dPlatfS'];
                        $departPlatformNormal = true;
                    } else {
                        // TODO: is this what we want when we don't know the platform?
                        $departPlatform = "?";
                        $departPlatformNormal = true;
                    }

                    if (key_exists('dTimeR', $trainRide['dep'])) {
                        $departDelay = tools::calculateSecondsHHMMSS($trainRide['dep']['dTimeR'],
                            $conn['date'], $trainRide['dep']['dTimeS'], $conn['date']);
                    } else {
                        $departDelay = 0;
                    }

                    if ($departDelay < 0) {
                        $departDelay = 0;
                    }

                    $arrivalTime = tools::transformTime($trainRide['arr']['aTimeS'],
                        $conn['date']);
                    if (key_exists('aPlatfR', $trainRide['arr'])) {
                        $arrivalPlatform = $trainRide['arr']['aPlatfR'];
                        $arrivalPlatformNormal = false;
                    } else if (key_exists('aPlatfS', $trainRide['arr'])) {
                        $arrivalPlatform = $trainRide['arr']['aPlatfS'];
                        $arrivalPlatformNormal = true;
                    } else {
                        // TODO: is this what we want when we don't know the platform?
                        $arrivalPlatform = "?";
                        $arrivalPlatformNormal = true;
                    }

                    if (key_exists('aTimeR', $trainRide['arr'])) {
                        $arrivalDelay = tools::calculateSecondsHHMMSS($trainRide['arr']['aTimeR'],
                            $conn['date'], $trainRide['arr']['aTimeS'], $conn['date']);
                    } else {
                        $arrivalDelay = 0;
                    }

                    if ($arrivalDelay < 0) {
                        $arrivalDelay = 0;
                    }

                    $arrivalIsExtraStop = 0;
                    if (key_exists('isAdd', $trainRide['arr'])) {
                        $arrivalIsExtraStop = $trainRide['arr']['isAdd'];
                    }

                    $departureIsExtraStop = 0;
                    if (key_exists('isAdd', $trainRide['dep'])) {
                        $departureIsExtraStop = $trainRide['dep']['isAdd'];
                    }

                    $departurecanceled = false;
                    $arrivalcanceled = false;

                    if (key_exists('dCncl', $trainRide['dep'])) {
                        $departurecanceled = $trainRide['dep']['dCncl'];
                    }

                    if (key_exists('aCncl', $trainRide['arr'])) {
                        $arrivalcanceled = $trainRide['arr']['aCncl'];
                    }

                    $trains[$trainIndex] = new StdClass();
                    $trains[$trainIndex]->arrival = new ViaDepartureArrival();
                    $trains[$trainIndex]->arrival->time = tools::transformTime($trainRide['arr']['aTimeS'], $conn['date']);
                    $trains[$trainIndex]->arrival->delay = $arrivalDelay;
                    $trains[$trainIndex]->arrival->platform = new Platform();
                    $trains[$trainIndex]->arrival->platform->name = $arrivalPlatform;
                    $trains[$trainIndex]->arrival->platform->normal = $arrivalPlatformNormal;
                    $trains[$trainIndex]->arrival->canceled = $arrivalcanceled;
                    $trains[$trainIndex]->arrival->isExtraStop = $arrivalIsExtraStop;
                    $trains[$trainIndex]->departure = new ViaDepartureArrival();
                    $trains[$trainIndex]->departure->time = tools::transformTime($trainRide['dep']['dTimeS'], $conn['date']);
                    $trains[$trainIndex]->departure->delay = $departDelay;
                    $trains[$trainIndex]->departure->platform = new Platform();
                    $trains[$trainIndex]->departure->platform->name = $departPlatform;
                    $trains[$trainIndex]->departure->platform->normal = $departPlatformNormal;
                    $trains[$trainIndex]->departure->canceled = $departurecanceled;
                    $trains[$trainIndex]->departure->isExtraStop = $departureIsExtraStop;
                    $trains[$trainIndex]->duration = Tools::calculateSecondsHHMMSS($arrivalTime, $conn['date'],
                        $departTime, $conn['date']);

                    var_dump($trainRide);
                    var_dump($trainRide['jny']);

                    // When reported, either marked by progType or by InReported/OutReported. TODO: verify if this is correct. No documentation or source.
                    if ((key_exists('dProgType', $trainRide['dep']) && $trainRide['dep']['dProgType'] == "REPORTED")
                        || (key_exists('dInR', $trainRide['dep']) && $trainRide['dep']['dInR'] == "true")) {
                        $trains[$trainIndex]->left = 1;
                    } else {
                        $trains[$trainIndex]->left = 0;
                    }

                    if ((key_exists('aProgType', $trainRide['arr']) && $trainRide['arr']['aProgType'] == "REPORTED")
                        || (key_exists('aOutR', $trainRide['arr']) && $trainRide['arr']['aOutR'] == "true")) {
                        $trains[$trainIndex]->arrived = 1;
                        // A train can only arrive if it left first in the previous station
                        $trains[$trainIndex]->left = 1;
                    } else {
                        $trains[$trainIndex]->arrived = 0;
                    }

                    $trains[$trainIndex]->departure->station = Stations::getStationFromID($locationDefinitions[$trainRide['dep']['locX']]->id,
                        $lang);
                    $trains[$trainIndex]->arrival->station = Stations::getStationFromID($locationDefinitions[$trainRide['arr']['locX']]->id,
                        $lang);

                    $trains[$trainIndex]->isPartiallyCancelled = false;
                    $trains[$trainIndex]->stops = [];
                    if (key_exists('jny', $trainRide)) {
                        if (key_exists('isPartCncl', $trainRide['jny'])) {
                            $trains[$trainIndex]->isPartiallyCancelled = $trainRide['jny']['isPartCncl'];
                        }

                        foreach ($trainRide['jny']['stopL'] as $rawIntermediateStop) {

                            /* "locX": 2,
                          "idx": 19,
                          "aProdX": 1,
                          "aTimeS": "162900",
                          "aTimeR": "162900",
                          "aProgType": "PROGNOSED",
                          "dProdX": 1,
                          "dTimeS": "163000",
                          "dTimeR": "163000",
                          "dProgType": "PROGNOSED",
                          "isImp": true
                                */
                            $intermediateStop = new StdClass();
                            $intermediateStop->station = Stations::getStationFromID($locationDefinitions[$rawIntermediateStop['locX']]->id,
                                $lang);


                            if (key_exists('aProgType', $rawIntermediateStop)) {
                                $intermediateStop->scheduledArrivalTime = tools::transformTime($rawIntermediateStop['aTimeS'],
                                    $conn['date']);

                                $intermediateStop->arrivalCanceled = self::arrivalCanceled($rawIntermediateStop['aProgType']);

                                if (key_exists('aTimeR', $rawIntermediateStop)) {
                                    $intermediateStop->arrivalDelay = tools::calculateSecondsHHMMSS($rawIntermediateStop['aTimeR'],
                                        $conn['date'], $rawIntermediateStop['aTimeS'], $conn['date']);
                                } else {
                                    $intermediateStop->arrivalDelay = 0;
                                }

                                if ($rawIntermediateStop['aProgType'] == "REPORTED") {
                                    $intermediateStop->arrived = 1;
                                } else {
                                    $intermediateStop->arrived = 0;
                                }
                            }

                            if (key_exists('dProgType', $rawIntermediateStop)) {
                                if (key_exists('dTimeS', $rawIntermediateStop)) {
                                    $intermediateStop->scheduledDepartureTime = tools::transformTime($rawIntermediateStop['dTimeS'],
                                        $conn['date']);
                                } else {
                                    // TODO: ensure this doesn't cause trouble in the printer
                                    $intermediateStop->scheduledDepartureTime = null;
                                }

                                if (key_exists('dTimeR', $rawIntermediateStop)) {
                                    $intermediateStop->departureDelay = tools::calculateSecondsHHMMSS($rawIntermediateStop['dTimeR'],
                                        $conn['date'], $rawIntermediateStop['dTimeS'],
                                        $conn['date']);
                                } else {
                                    $intermediateStop->departureDelay = 0;
                                }

                                $intermediateStop->departureCanceled = self::departureCanceled($rawIntermediateStop['dProgType']);

                                if ($rawIntermediateStop['dProgType'] == "REPORTED") {
                                    $intermediateStop->left = 1;
                                    // A train can only leave a stop if he arrived first
                                    $intermediateStop->arrived = 1;
                                } else {
                                    $intermediateStop->left = 0;
                                }
                            }

                            // Some boolean about scheduled departure? First seen on an added stop
                            if (key_exists('dInS', $rawIntermediateStop)) {
                            }

                            // Some boolean about realtime departure? First seen on an added stop
                            if (key_exists('dInR', $rawIntermediateStop)) {
                            }

                            // Some boolean about realtime departure? First seen on an added stop
                            if (key_exists('aOutR', $rawIntermediateStop)) {
                            }

                            if (key_exists('dCncl', $rawIntermediateStop)) {
                                $intermediateStop->departureCanceled = $rawIntermediateStop['dCncl'];
                            }

                            if (key_exists('aCncl', $rawIntermediateStop)) {
                                $intermediateStop->arrivalCanceled = $rawIntermediateStop['aCncl'];
                            }

                            if (key_exists('isAdd', $rawIntermediateStop)) {
                                $intermediateStop->isExtraStop = 1;
                            } else {
                                $intermediateStop->isExtraStop = 0;
                            }

                            $trains[$trainIndex]->stops[] = $intermediateStop;
                        }

                        // Don't trust this code yet
                        $trains[$trainIndex]->alerts = [];
                        try {
                            if (key_exists('himL', $trainRide['jny']) && is_array($trainRide['jny']['himL'])) {
                                foreach ($trainRide['jny']['himL'] as $himX) {
                                    $trains[$trainIndex]->alerts[] = $alertDefinitions[$himX['himX']];
                                }
                            }
                        } catch (Exception $ignored) {
                            // ignored
                        }
                    }

                    if ($trainRide['type'] == 'WALK') {
                        // If the type is walking, there is no direction. Resolve this by hardcoding this variable.
                        $trains[$trainIndex]->direction = new StdClass();
                        $trains[$trainIndex]->direction->name = "WALK";
                        $trains[$trainIndex]->vehicle = 'WALK';
                        $trains[$trainIndex]->walking = 1;
                    } else {
                        $trains[$trainIndex]->walking = 0;
                        $trains[$trainIndex]->direction = new StdClass();
                        if (key_exists('dirTxt', $trainRide['jny'])) {
                            // Get the direction from the API
                            $trains[$trainIndex]->direction->name = $trainRide['jny']['dirTxt'];
                        } else {
                            // If we can't load the direction from the data (direction is missing),
                            // fill in the gap by using the furthest stop we know on this trains route.
                            // This typically is the stop where the user leaves this train
                            $trains[$trainIndex]->direction->name = end($trains[$trainIndex]->stops)->station->name;
                        }
                        $trains[$trainIndex]->vehicle = 'BE.NMBS.' . $vehicleDefinitions[$trainRide['jny']['prodX']]->name;
                    }

                    $trainIndex++;
                }

                $connection[$i]->departure->canceled = $trains[0]->departure->canceled;;
                $connection[$i]->arrival->canceled = end($trains)->arrival->canceled;

                // Don't need this variable anymore. Clean up for easier debugging.
                unset($trainIndex);

                $viaCount = count($trains) - 1;
                for ($viaIndex = 0; $viaIndex < $viaCount; $viaIndex++) {
                    $vias[$viaIndex] = new Via();
                    $vias[$viaIndex]->arrival = new ViaDepartureArrival();
                    $vias[$viaIndex]->arrival->time = $trains[$viaIndex]->arrival->time;
                    $vias[$viaIndex]->arrival->delay = $trains[$viaIndex]->arrival->delay;
                    $vias[$viaIndex]->arrival->platform = $trains[$viaIndex]->arrival->platform;
                    $vias[$viaIndex]->arrival->canceled = $trains[$viaIndex]->arrival->canceled;
                    $vias[$viaIndex]->arrival->isExtraStop = $trains[$viaIndex]->arrival->isExtraStop;
                    // No alerts for arrival objects
                    /*if (property_exists($trains[$viaIndex], 'alerts') && count($trains[$viaIndex]->alerts) > 0) {
                        $vias[$viaIndex]->arrival->alert = $trains[$viaIndex]->alerts;
                    }*/

                    $vias[$viaIndex]->arrival->arrived = $trains[$viaIndex]->arrived;

                    $vias[$viaIndex]->departure = new ViaDepartureArrival();
                    $vias[$viaIndex]->departure->time = $trains[$viaIndex + 1]->departure->time;
                    $vias[$viaIndex]->departure->delay = $trains[$viaIndex + 1]->departure->delay;
                    $vias[$viaIndex]->departure->platform = $trains[$viaIndex + 1]->departure->platform;
                    $vias[$viaIndex]->departure->canceled = $trains[$viaIndex + 1]->departure->canceled;
                    $vias[$viaIndex]->departure->isExtraStop = $trains[$viaIndex + 1]->departure->isExtraStop;
                    if (property_exists($trains[$viaIndex + 1],
                            'alerts') && count($trains[$viaIndex + 1]->alerts) > 0) {
                        $vias[$viaIndex]->departure->alert = $trains[$viaIndex + 1]->alerts;
                    }

                    $vias[$viaIndex]->departure->left = $trains[$viaIndex + 1]->left;

                    $vias[$viaIndex]->timeBetween = $vias[$viaIndex]->departure->time - $trains[$viaIndex]->arrival->time;
                    $vias[$viaIndex]->direction = $trains[$viaIndex]->direction;
                    $vias[$viaIndex]->arrival->walking = $trains[$viaIndex]->walking;

                    $vias[$viaIndex]->arrival->direction = $trains[$viaIndex]->direction;

                    $vias[$viaIndex]->departure->walking = $trains[$viaIndex + 1]->walking;
                    $vias[$viaIndex]->departure->direction = $trains[$viaIndex + 1]->direction;

                    $vias[$viaIndex]->vehicle = $trains[$viaIndex]->vehicle;
                    $vias[$viaIndex]->arrival->vehicle = $trains[$viaIndex]->vehicle;
                    $vias[$viaIndex]->departure->vehicle = $trains[$viaIndex + 1]->vehicle;
                    // TODO: evaluate if we want to include the intermediate stops, and if so, where
                    //$vias[$viaIndex]->nextIntermediateStop = $trains[$viaIndex + 1]->stops;
                    $vias[$viaIndex]->station = $trains[$viaIndex]->arrival->station;

                    $vias[$viaIndex]->departure->departureConnection = 'http://irail.be/connections/' . substr(basename($vias[$viaIndex]->station->{'@id'}),
                            2) . '/' . date('Ymd',
                            $departTime) . '/' . substr($vias[$viaIndex]->departure->vehicle,
                            strrpos($vias[$viaIndex]->departure->vehicle, '.') + 1);
                    $vias[$viaIndex]->arrival->departureConnection = 'http://irail.be/connections/' . substr(basename($vias[$viaIndex]->station->{'@id'}),
                            2) . '/' . date('Ymd',
                            $departTime) . '/' . substr($vias[$viaIndex]->arrival->vehicle,
                            strrpos($vias[$viaIndex]->arrival->vehicle, '.') + 1);
                }

                // All the train alerts should go together in the connection alerts
                $connectionAlerts = [];
                foreach ($trains as $train) {
                    if (property_exists($train, 'alerts')) {
                        $connectionAlerts = array_merge($connectionAlerts, $train->alerts);
                    }
                }
                $connectionAlerts = array_unique($connectionAlerts, SORT_REGULAR);

                $connectionRemarks = [];
                if (key_exists('ovwMsgL', $conn)) {
                    foreach ($conn['ovwMsgL'] as $message) {
                        $connectionRemarks[] = $remarkDefinitions[$message['remX']];
                    }
                }
                if (key_exists('footerMsgL', $conn)) {
                    foreach ($conn['footerMsgL'] as $message) {
                        $connectionRemarks[] = $remarkDefinitions[$message['remX']];
                    }
                }


                if (count($connectionAlerts) > 0) {
                    $connection[$i]->alert = $connectionAlerts;
                }


                if (count($connectionRemarks) > 0) {
                    $connection[$i]->remark = $connectionRemarks;
                }

                //check if there were vias at all. Ignore the first
                if ($viaCount != 0) {
                    //if there were vias, add them to the array
                    $connection[$i]->via = $vias;
                }

                $connection[$i]->departure->vehicle = $trains[0]->vehicle;
                // TODO: evaluate if we want to include the intermediate stops, and if so, where
                //$connection[$i]->departure->nextIntermediateStop = $trains[0]->stops;

                $connection[$i]->departure->departureConnection = 'http://irail.be/connections/' . substr(basename($departureStation->{'@id'}),
                        2) . '/' . date('Ymd', $connection[$i]->departure->time) . '/' . substr($trains[0]->vehicle, strrpos($trains[0]->vehicle, '.') + 1);


                $connection[$i]->departure->direction = $trains[0]->direction;
                $connection[$i]->departure->left = $trains[0]->left;

                $connection[$i]->departure->walking = 0;
                if (property_exists($trains[0], 'alerts') && count($trains[0]->alerts) > 0) {
                    $connection[$i]->departure->alert = $trains[0]->alerts;
                }

                $connection[$i]->arrival->vehicle = $trains[count($trains) - 1]->vehicle;
                $connection[$i]->arrival->direction = $trains[count($trains) - 1]->direction;
                $connection[$i]->arrival->arrived = end($trains)->arrived;
                $connection[$i]->arrival->walking = 0;
                // No alerts for arrival objects
                /*if (property_exists(end($trains), 'alerts') && count(end($trains)->alerts) > 0) {
                    $connection[$i]->arrival->alert = end($trains)->alerts;
                }*/

                //Add journey options to the logs of iRail
                $journeyoptions[$i] = ["journeys" => []];
                $departureStop = $connection[$i]->departure->station;
                for ($viaIndex = 0; $viaIndex < count($vias); $viaIndex++) {
                    $arrivalStop = $vias[$viaIndex]->station;
                    $journeyoptions[$i]["journeys"][] = [
                        "trip"          => substr($vias[$viaIndex]->vehicle, 8),
                        "departureStop" => $departureStop->{'@id'},
                        "arrivalStop"   => $arrivalStop->{'@id'}
                    ];
                    //set the next departureStop
                    $departureStop = $vias[$viaIndex]->station;
                }
                //add last journey
                $journeyoptions[$i]["journeys"][] = [
                    "trip"          => substr($connection[$i]->arrival->vehicle, 8),
                    "departureStop" => $departureStop->{'@id'},
                    "arrivalStop"   => $connection[$i]->arrival->station->{'@id'}
                ];
                $request->setJourneyOptions($journeyoptions);
                $i++;
            }
        } else {
            throw new Exception("We're sorry, we could not parse the correct data from our sources", 500);
        }

        return $connection;
    }

    private static function addOccupancy($connections, $date)
    {
        $occupancyConnections = $connections;

        // Use this to check if the MongoDB module is set up. If not, the occupancy score will not be returned.
        $mongodbExists = true;
        $i = 0;

        try {
            while ($i < count($occupancyConnections) && $mongodbExists) {
                $departure = $occupancyConnections[$i]->departure;
                $vehicle = $departure->vehicle;
                $from = $departure->station->{"@id"};

                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($vehicle, "."), 1);
                $occupancyURI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                if (!is_null($occupancyURI)) {
                    $occupancyArr = [];

                    $occupancyConnections[$i]->departure->occupancy = new \stdClass();
                    $occupancyConnections[$i]->departure->occupancy->{'@id'} = $occupancyURI;
                    $occupancyConnections[$i]->departure->occupancy->name = basename($occupancyURI);
                    array_push($occupancyArr, $occupancyURI);

                    if (isset($occupancyConnections[$i]->via)) {
                        foreach ($occupancyConnections[$i]->via as $key => $via) {
                            if ($key < count($occupancyConnections[$i]->via) - 1) {
                                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($occupancyConnections[$i]->via[$key + 1]->vehicle, "."), 1);
                            } else {
                                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($occupancyConnections[$i]->arrival->vehicle, "."), 1);
                            }

                            $from = $via->station->{'@id'};

                            $occupancyURI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                            $via->departure->occupancy = new \stdClass();
                            $via->departure->occupancy->{'@id'} = $occupancyURI;
                            $via->departure->occupancy->name = basename($occupancyURI);
                            array_push($occupancyArr, $occupancyURI);
                        }
                    }

                    $occupancyURI = OccupancyOperations::getMaxOccupancy($occupancyArr);

                    $occupancyConnections[$i]->occupancy = new \stdClass();
                    $occupancyConnections[$i]->occupancy->{'@id'} = $occupancyURI;
                    $occupancyConnections[$i]->occupancy->name = basename($occupancyURI);
                    $i++;
                } else {
                    $mongodbExists = false;
                }
            }
        } catch (Exception $e) {
            // Here one can implement a reporting to the iRail owner that the database has problems.
            return $connections;
        }

        return $occupancyConnections;
    }

    private static function departureCanceled($status)
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

    private static function arrivalCanceled($status)
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
}
