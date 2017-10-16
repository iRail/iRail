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
            $request->setTo($to);
            $to = $to->name;
        }
        $dataroot->connection = self::scrapeConnections($from, $to, $request->getTime(), $request->getDate(), $request->getResults(), $request->getLang(), $request->getFast(), $request->getAlerts(), $request->getTimeSel(), $request->getTypeOfTransport(), $request);
    }

    /**
     * @param $from
     * @param $to
     * @param $time
     * @param $date
     * @param $results
     * @param $lang
     * @param $fast
     * @param bool $showAlerts
     * @param string $timeSel
     * @param string $typeOfTransport
     * @return array
     * @throws Exception
     */
    private static function scrapeConnections($from, $to, $time, $date, $results, $lang, $fast, $showAlerts, $timeSel = 'depart', $typeOfTransport = 'trains', $request)
    {
        $ids = self::getHafasIDsFromNames($from, $to, $lang, $request);

        $nmbsCacheKey = self::getNmbsCacheKey($ids[0], $ids[1], $lang, $time, $date, $results, $timeSel,
            $typeOfTransport);
        $xml = Tools::getCachedObject($nmbsCacheKey);

        if ($xml === false) {
            $xml = self::requestHafasXml($ids[0], $ids[1], $lang, $time, $date, $results, $timeSel, $typeOfTransport);
            Tools::setCachedObject($nmbsCacheKey, $xml);
        }

        $connections = self::parseHafasXml($xml, $lang, $fast, $request, $showAlerts, $request->getFormat());

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
     * This function scrapes the ID from the HAFAS system. Since hafas IDs will be requested in pairs, it also returns 2 id's and asks for 2 names.
     *
     * @param $from
     * @param $to
     * @param $lang
     * @return array
     */
    private static function getHafasIDsFromNames($from, $to, $lang, $request)
    {
        try {
            $station1 = stations::getStationFromName($from, $lang);
        
            $station2 = stations::getStationFromName($to, $lang);
            if (isset($request)) {
                $request->setFrom($station1);
                $request->setTo($station2);
            }
            return [$station1->getHID(), $station2->getHID()];
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 404);
        }
    }

    /**
     * @param $idfrom
     * @param $idto
     * @param $lang
     * @param $time
     * @param $date
     * @param $results
     * @param $timeSel
     * @param $typeOfTransport
     * @return mixed
     */
    private static function requestHafasXml($idfrom, $idto, $lang, $time, $date, $results, $timeSel, $typeOfTransport)
    {
        include '../includes/getUA.php';
        $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/mgate.exe";
        // OLD URL: $url = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/extxml.exe';
        // OLDER URL: $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";

        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => $irailAgent,
        ];
        if ($typeOfTransport == 'trains') {
            $trainsonly = '1111111000000000';
        } elseif ($typeOfTransport == 'nointernationaltrains') {
            $trainsonly = '0111111000000000';
        } elseif ($typeOfTransport == 'all') {
            $trainsonly = '1111111111111111';
        } else {
            $trainsonly = '1111111000000000';
        }

        if ($timeSel == 'depart') {
            $timeSel = 0;
        } elseif ($timeSel == 'arrive') {
            $timeSel = 1;
        } else {
            $timeSel = 1;
        }

        // numF: number of results: server-side capped to 5, but ask 10 in case they'd let us
        $postdata = '{"auth":{"aid":"sncb-mobi","type":"AID"},
        "client":{"id":"SNCB","name":"NMBS","os":"Android 5.0.2","type":"AND","ua":"SNCB/302132 (Android_5.0.2) Dalvik/2.1.0 (Linux; U; Android 5.0.2; HTC One Build/LRX22G)","v":302132},
        "lang":"' . $lang . '",
        "svcReqL":[
            {
                "cfg":{"polyEnc":"GPA"},
                "meth":"TripSearch",
                "req":{
                    "arrLocL":[{"lid":"L=' . $idto . '@B=1@p=1429490515@","type":"S"}],
                    "depLocL":[{"lid":"L=' . $idfrom . '@B=1@p=1481329402@n=ac.1=GA@","type":"S"}],
                    "jnyFltrL":[{"mode":"BIT","type":"PROD","value":"' . $trainsonly . '"}],
                    "outDate":"' . $date . '",
                    "outTime":"' . str_replace(':','',$time) . '00",
                    "economic":false,
                    "extChgTime":-1,
                    "getIST":false,
                    "getPasslist":true,
                    "getPolyline":false,
                    "numF":10,
                    "liveSearch":true
                }
            }
        ],
        "ver":"1.11","formatted":false}';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function parseHafasXml($serverData, $lang, $fast, $request, $showAlerts = false, $format)
    {
        $json = json_decode($serverData, true);

        if ($json['svcResL'][0]['err'] == "H9360") {
            throw new Exception("Date outside of the timetable period.", 404);
        }

        $connection = [];
        $journeyoptions = [];
        $i = 0;
        if ($json['svcResL'][0]['err'] == 'OK') {
            $fromstation = Stations::getStationFromID('00' . $json['svcResL'][0]['res']['common']['locL'][0]['extId'],
                $lang);
            $tostation = Stations::getStationFromID('00' . $json['svcResL'][0]['res']['common']['locL'][1]['extId'],
                $lang);

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
                $connection[$i]->departure->station = $fromstation;

                if (key_exists('dTimeR', $conn['dep'])) {
                    $connection[$i]->departure->delay = $conn['dep']['dTimeR'] - $conn['dep']['dTimeS'];
                } else {
                    $connection[$i]->departure->delay = 0;
                }
                $connection[$i]->departure->time = tools::transformTimeHHMMSS($conn['dep']['dTimeS'], $conn['date']);

                //Delay and platform changes
                if (key_exists('dPlatfR',$conn['dep'])){
                    $departurePlatform = $conn['dep']['dPlatfR'];
                    $departurePlatformNormal = false;
                } else {
                    $departurePlatform = $conn['dep']['dPlatfS'];
                    $departurePlatformNormal = true;
                }

                if ($conn['dep']['dProgType'] == "SCHEDULED" ||
                    $conn['dep']['dProgType'] == "REPORTED" ||
                    $conn['dep']['dProgType'] == "PROGNOSED" ||
                    $conn['dep']['dProgType'] == "PARTIAL_FAILURE_AT_ARR") {
                    $departurecanceled = false;
                } else {
                    $departurecanceled = false;
                }

                $connection[$i]->departure->canceled = $departurecanceled;

                $connection[$i]->arrival = new DepartureArrival();
                $connection[$i]->arrival->station = $tostation;

                if (key_exists('aTimeR', $conn['arr'])) {
                    $connection[$i]->arrival->delay = $conn['arr']['aTimeR'] - $conn['arr']['aTimeS'];
                } else {
                    $connection[$i]->arrival->delay = 0;
                }
                //$connection[$i]->departure->delay = $conn['secL'][$numberOfVehicles - 1]['jny']['stopL'][$numberOfStopsInLastVehicle - 1]['dTimeS'] - $conn['secL'][$numberOfVehicles - 1]['jny']['stopL'][$numberOfStopsInLastVehicle - 1]['aTimeS'];
                $connection[$i]->arrival->time = tools::transformTimeHHMMSS($conn['arr']['aTimeS'], $conn['date']);

                //Delay and platform changes
                if (key_exists('aPlatfR',$conn['arr'])){
                    $arrivalPlatform = $conn['arr']['aPlatfR'];
                    $arrivalPlatformNormal = false;
                } else {
                    $arrivalPlatform = $conn['arr']['aPlatfS'];
                    $arrivalPlatformNormal = true;
                }

                if ($conn['arr']['aProgType'] == "SCHEDULED" ||
                    $conn['arr']['aProgType'] == "REPORTED" ||
                    $conn['arr']['aProgType'] == "PROGNOSED" ||
                    $conn['arr']['aProgType'] == "PARTIAL_FAILURE_AT_DEP") {
                    $arrivalcanceled = false;
                } else {
                    $arrivalcanceled = true;
                }
                $connection[$i]->arrival->canceled = $arrivalcanceled;

                // Alerts
                // TODO: support alerts
                /*
                if ($showAlerts && isset($conn->IList)) {
                    $alerts = [];
                    foreach ($conn->IList->I as $info) {
                        $alert = new Alert();
                        $alert->header = trim($info['header']);
                        $alert->description = trim(addslashes($info['text']));

                        // Keep <a> elements, those are valueable
                        $alert->description = strip_tags($alert->description, '<a>');

                        // Only encode json, since xml can use CDATA. Trim ", since these are added later on.
                        if ($format == 'json') {
                            $alert->description = trim(json_encode($alert->description), '"');
                        }

                        array_push($alerts, $alert);
                    }
                    $connection[$i]->alert = $alerts;
                }
                */

                $connection[$i]->departure->platform = new Platform();
                $connection[$i]->departure->platform->name = $departurePlatform;
                $connection[$i]->departure->platform->normal = $departurePlatformNormal;

                $connection[$i]->arrival->platform = new Platform();
                $connection[$i]->arrival->platform->name = $arrivalPlatform;
                $connection[$i]->arrival->platform->normal = $arrivalPlatformNormal;

                $trains = [];
                $vias = [];

                $connectionindex = 0;

                // For the sake of readability: the response contains trains, not vias. Therefore, just parse the trains, and create via's based on the trains later.
                // This is way more readable compared to instantly creating the vias
                // Loop over all train rides in the list. This will also include the first train ride.
                foreach ($conn['secL'] as $trainRide) {
                    $departTime = tools::transformTime($trainRide['dep']['dTimeS'],
                        $conn['date']);

                    if (key_exists('dPlatfR',$trainRide['dep'])){
                        $departPlatform = $trainRide['dep']['dPlatfR'];
                        $departPlatformNormal = false;
                    } else {
                        $departPlatform = $trainRide['dep']['dPlatfS'];
                        $departPlatformNormal = true;
                    }

                    if (key_exists('dTimeR', $trainRide['dep'])) {
                        $departDelay = $trainRide['dep']['dTimeR'] - $trainRide['dep']['dTimeS'];
                    } else {
                        $departDelay = 0;
                    }

                    if ($departDelay < 0) {
                        $departDelay = 0;
                    }

                    if ($trainRide['dep']['dProgType'] == "SCHEDULED" ||
                        $trainRide['dep']['dProgType'] == "REPORTED" ||
                        $trainRide['dep']['dProgType'] == "PROGNOSED" ||
                        $trainRide['dep']['dProgType'] == "PARTIAL_FAILURE_AT_ARR") {
                        $departcanceled = false;
                    } else {
                        $departcanceled = false;
                    }

                    $arrivalTime = tools::transformTime($trainRide['arr']['aTimeS'],
                        $conn['date']);
                    if (key_exists('aPlatfR',$trainRide['arr'])){
                        $arrivalPlatform = $trainRide['arr']['aPlatfR'];
                        $arrivalPlatformNormal = false;
                    } else {
                        $arrivalPlatform = $trainRide['arr']['aPlatfS'];
                        $arrivalPlatformNormal = true;
                    }

                    if (key_exists('aTimeR', $trainRide['arr'])) {
                        $arrivalDelay = $trainRide['arr']['aTimeR'] - $trainRide['arr']['aTimeS'];
                    } else {
                        $arrivalDelay = 0;
                    }

                    if ($arrivalDelay < 0) {
                        $arrivalDelay = 0;
                    }

                    if ($trainRide['arr']['aProgType'] == "SCHEDULED" ||
                        $trainRide['arr']['aProgType'] == "REPORTED" ||
                        $trainRide['arr']['aProgType'] == "PROGNOSED" ||
                        $trainRide['arr']['aProgType'] == "PARTIAL_FAILURE_AT_DEP") {
                        $arrivalcanceled = false;
                    } else {
                        $arrivalcanceled = true;
                    }

                    $trains[$connectionindex] = new StdClass();
                    $trains[$connectionindex]->arrival = new ViaDepartureArrival();
                    $trains[$connectionindex]->arrival->time = tools::transformTimeHHMMSS($trainRide['arr']['aTimeS'],$conn['date']);
                    $trains[$connectionindex]->arrival->delay = $arrivalDelay;
                    $trains[$connectionindex]->arrival->platform = new Platform();
                    $trains[$connectionindex]->arrival->platform->name = $arrivalPlatform;
                    $trains[$connectionindex]->arrival->platform->normal = $arrivalPlatformNormal;
                    $trains[$connectionindex]->arrival->canceled = $arrivalcanceled;
                    $trains[$connectionindex]->departure = new ViaDepartureArrival();
                    $trains[$connectionindex]->departure->time = tools::transformTimeHHMMSS($trainRide['dep']['dTimeS'],$conn['date']);
                    $trains[$connectionindex]->departure->delay = $departDelay;
                    $trains[$connectionindex]->departure->platform = new Platform();
                    $trains[$connectionindex]->departure->platform->name = $departPlatform;
                    $trains[$connectionindex]->departure->platform->normal = $departPlatformNormal;
                    $trains[$connectionindex]->departure->canceled = $departcanceled;
                    $trains[$connectionindex]->duration = $arrivalTime - $departTime;

                    $trains[$connectionindex]->direction = new StdClass();
                    $trains[$connectionindex]->direction->name = $trainRide['jny']['dirTxt'];

                    if ($trainRide['dep']['dProgType'] == "REPORTED") {
                        $trains[$connectionindex]->left = 1;
                    } else {
                        $trains[$connectionindex]->left = 0;
                    }

                    if ($trainRide['arr']['aProgType'] == "REPORTED") {
                        $trains[$connectionindex]->arrived = 1;
                    } else {
                        $trains[$connectionindex]->arrived = 0;
                    }

                    $ctxRecon = trim($trainRide['jny']['ctxRecon'], '$');
                    $trainName = explode('$', $ctxRecon);
                    $trainName = end($trainName);
                    $trains[$connectionindex]->vehicle = 'BE.NMBS.' . str_replace(' ', '', $trainName);

                    if ($connectionindex == 0) {
                        $trains[$connectionindex]->departureStation = $fromstation;
                    } else {
                        $trains[$connectionindex]->departureStation = Stations::getStationFromID('00' . $json['svcResL'][0]['res']['common']['locL'][$connectionindex + 1]['extId'],
                            $lang);
                    }
                    $connectionindex++;
                }

                $connectionindex = 0;
                $viaCount = count($trains) - 1;
                for ($viaIndex = 0; $viaIndex < $viaCount; $viaIndex++) {
                    $vias[$viaIndex] = new Via();
                    $vias[$viaIndex]->arrival = new ViaDepartureArrival();
                    $vias[$viaIndex]->arrival->time = $trains[$viaIndex]->arrival->time;
                    $vias[$viaIndex]->arrival->delay = $trains[$viaIndex]->arrival->delay;
                    $vias[$viaIndex]->arrival->platform = $trains[$viaIndex]->arrival->platform;
                    $vias[$viaIndex]->arrival->canceled = $trains[$viaIndex]->arrival->canceled;

                    $vias[$viaIndex]->arrival->arrived = $trains[$viaIndex]->arrived;

                    $vias[$viaIndex]->departure = new ViaDepartureArrival();
                    $vias[$viaIndex]->departure->time = $trains[$viaIndex + 1]->departure->time;
                    $vias[$viaIndex]->departure->delay = $trains[$viaIndex + 1]->departure->delay;
                    $vias[$viaIndex]->departure->platform = $trains[$viaIndex + 1]->departure->platform;
                    $vias[$viaIndex]->departure->canceled = $trains[$viaIndex + 1]->departure->canceled;

                    $vias[$viaIndex]->departure->left = $trains[$viaIndex + 1]->left;

                    $vias[$viaIndex]->timeBetween = $vias[$viaIndex]->departure->time - $trains[$viaIndex]->arrival->time;

                    $vias[$viaIndex]->direction = $trains[$viaIndex]->direction;
                    $vias[$viaIndex]->arrival->direction = $trains[$viaIndex]->direction;
                    $vias[$viaIndex]->departure->direction = $trains[$viaIndex + 1]->direction;

                    $vias[$viaIndex]->vehicle = $trains[$viaIndex]->vehicle;
                    $vias[$viaIndex]->arrival->vehicle = $trains[$viaIndex]->vehicle;
                    $vias[$viaIndex]->departure->vehicle = $trains[$viaIndex + 1]->vehicle;

                    $vias[$viaIndex]->station = $trains[$viaIndex + 1]->departureStation;

                    $vias[$connectionindex]->departure->departureConnection = 'http://irail.be/connections/' . substr(basename($vias[$connectionindex]->station->{'@id'}),
                            2) . '/' . date('Ymd',
                            $departTime) . '/' . substr($vias[$connectionindex]->departure->vehicle,
                            strrpos($vias[$connectionindex]->departure->vehicle, '.') + 1);
                    $vias[$connectionindex]->arrival->departureConnection = 'http://irail.be/connections/' . substr(basename($vias[$connectionindex]->station->{'@id'}),
                            2) . '/' . date('Ymd',
                            $departTime) . '/' . substr($vias[$connectionindex]->arrival->vehicle,
                            strrpos($vias[$connectionindex]->arrival->vehicle, '.') + 1);
                }


                //check if there were vias at all. Ignore the first
                if ($viaCount != 0) {
                    //if there were vias, add them to the array
                    $connection[$i]->via = $vias;
                }

                $connection[$i]->departure->vehicle = $trains[0]->vehicle;
                $connection[$i]->departure->departureConnection = 'http://irail.be/connections/' . substr(basename($fromstation->{'@id'}), 2) . '/' . date('Ymd', $connection[$i]->departure->time) . '/' . $trains[0]->vehicle;
                $connection[$i]->departure->direction = $trains[0]->direction;
                $connection[$i]->departure->left = $trains[0]->left;

                $connection[$i]->arrival->vehicle = $trains[count($trains) - 1]->vehicle;
                $connection[$i]->arrival->direction = $trains[count($trains) - 1]->direction;
                $connection[$i]->arrival->left = end($trains)->arrived;

                //Add journey options to the logs of iRail
                $journeyoptions[$i] = ["journeys" => [] ];
                $departureStop = $connection[$i]->departure->station;
                for ($viaindex = 0; $viaindex < count($vias); $viaindex++) {
                    $arrivalStop = $vias[$viaindex]->station;
                    $journeyoptions[$i]["journeys"][] = [
                        "trip" => substr($vias[$viaindex]->vehicle, 8),
                        "departureStop" => $departureStop->{'@id'},
                        "arrivalStop" => $arrivalStop->{'@id'}
                    ];
                    //set the next departureStop
                    $departureStop = $vias[$viaindex]->station;
                }
                //add last journey
                $journeyoptions[$i]["journeys"][] = [
                    "trip" => substr($connection[$i]->arrival->vehicle, 8),
                    "departureStop" => $departureStop->{'@id'},
                    "arrivalStop" => $connection[$i]->arrival->station->{'@id'}
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

    /**
     * @param $locationX
     * @param $locationY
     * @param $lang
     * @return Station
     */
    private static function getStationFromHafasDescription($name, $locationX, $locationY, $lang)
    {
        return stations::getStationFromName($name, $lang);
    }
}
