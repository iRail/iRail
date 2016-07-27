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
        //detect whether from was an id and change from accordingly
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
        $xml = self::requestHafasXml($ids[0], $ids[1], $lang, $time, $date, $results, $timeSel, $typeOfTransport);
        $connections = self::parseHafasXml($xml, $lang, $fast, $request, $showAlerts);

        $requestedDate = DateTime::createFromFormat('Ymd', $date);
        $now = new DateTime();
        $daysDiff = $now->diff($requestedDate);

        if (intval($daysDiff->format('%R%a')) >= 2) {
            return $connections;
        } else {
            return self::addOccupancy($connections, $date);
        }
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
        $station1 = stations::getStationFromName($from, $lang);
        $station2 = stations::getStationFromName($to, $lang);
        if (isset($request)) {
            $request->setFrom($station1);
            $request->setTo($station2);
        }
        return [$station1->getHID(), $station2->getHID()];
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
        $url = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/extxml.exe';
        //OLD URL: $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
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

        //now we're going to get the real data
        $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="iRail" lang="'.$lang.'">
<ConReq>
<Start min="0">
<Station externalId="'.$idfrom.'" distance="0">
</Station>
<Prod prod="'.$trainsonly.'">
</Prod>
</Start>
<Dest min="0">
<Station externalId="'.$idto.'" distance="0">
</Station>
</Dest>
<Via>
</Via>
<ReqT time="'.$time.'" date="'.$date.'" a="'.$timeSel.'">
</ReqT>
<RFlags b="'.$results * $timeSel.'" f="'.$results * -($timeSel - 1).'">
</RFlags>
<GISParameters>
<Front>
</Front>
<Back>
</Back>
</GISParameters>
</ConReq>
</ReqC>';

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

    public static function parseHafasXml($serverData, $lang, $fast, $request, $showAlerts = false)
    {
        $xml = new SimpleXMLElement($serverData);
        $connection = [];
        $journeyoptions = [];
        $i = 0;
        if (isset($xml->ConRes->ConnectionList->Connection)) {
            $fromstation = self::getStationFromHafasDescription($xml->ConRes->ConnectionList->Connection[0]->Overview->Departure->BasicStop->Station['name'], $xml->ConRes->ConnectionList->Connection[0]->Overview->Departure->BasicStop->Station['x'], $xml->ConRes->ConnectionList->Connection[0]->Overview->Departure->BasicStop->Station['y'], $lang);
            $tostation = self::getStationFromHafasDescription($xml->ConRes->ConnectionList->Connection[0]->Overview->Arrival->BasicStop->Station['name'], $xml->ConRes->ConnectionList->Connection[0]->Overview->Arrival->BasicStop->Station['x'], $xml->ConRes->ConnectionList->Connection[0]->Overview->Arrival->BasicStop->Station['y'], $lang);

            foreach ($xml->ConRes->ConnectionList->Connection as $conn) {
                $connection[$i] = new Connection();
                $connection[$i]->duration = tools::transformDuration($conn->Overview->Duration->Time);

                $connection[$i]->departure = new DepartureArrival();
                $connection[$i]->departure->station = $fromstation;
                $connection[$i]->departure->direction = (trim($conn->Overview->Departure->BasicStop->Dep->Platform->Text));
                $connection[$i]->departure->time = tools::transformTime($conn->Overview->Departure->BasicStop->Dep->Time, $conn->Overview->Date);

                if ($conn->Overview->Departure->BasicStop->StopPrognosis->Status == "SCHEDULED" ||
                    $conn->Overview->Departure->BasicStop->StopPrognosis->Status == "PARTIAL_FAILURE_AT_ARR") {
                    $departurecanceled = false;
                } else {
                    $departurecanceled = true;
                }
                $connection[$i]->departure->canceled = $departurecanceled;

                $connection[$i]->arrival = new DepartureArrival();
                $connection[$i]->arrival->station = $tostation;
                $connection[$i]->arrival->time = tools::transformTime($conn->Overview->Arrival->BasicStop->Arr->Time, $conn->Overview->Date);

                if ($conn->Overview->Arrival->BasicStop->StopPrognosis->Status == "SCHEDULED" ||
                    $conn->Overview->Arrival->BasicStop->StopPrognosis->Status == "PARTIAL_FAILURE_AT_DEP") {
                    $arrivalcanceled = false;
                } else {
                    $arrivalcanceled = true;
                }
                $connection[$i]->arrival->canceled = $arrivalcanceled;

                //Delay and platform changes
                $departureDelay = 0;
                $departurePlatform = trim($conn->Overview->Departure->BasicStop->Dep->Platform->Text);
                $departurePlatformNormal = true;

                $arrivalDelay = 0;
                $arrivalPlatform = trim($conn->Overview->Arrival->BasicStop->Arr->Platform->Text);
                $arrivalPlatformNormal = true;

                if ($conn->RtStateList->RtState['value'] == 'HAS_DELAYINFO' or $conn->RtStateList->RtState['value'] == 'IS_ALTERNATIVE') {
                    //echo "delay: " .$conn->Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Time . "\n";
                    $departureDelay = tools::transformTime($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Time, $conn->Overview->Date) - $connection[$i]->departure->time;
                    if ($departureDelay < 0) {
                        $departureDelay = 0;
                    }
                    $arrivalDelay = tools::transformTime($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Time, $conn->Overview->Date) - $connection[$i]->arrival->time;
                    if ($arrivalDelay < 0) {
                        $arrivalDelay = 0;
                    }
                    if (isset($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Platform->Text)) {
                        $departurePlatform = trim($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Platform->Text);
                        $departurePlatformNormal = false;
                    }
                    if (isset($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text)) {
                        $arrivalPlatform = trim($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text);
                        $arrivalPlatformNormal = false;
                    }
                }

                // Alerts
                if ($showAlerts && isset($conn->IList)) {
                    $alerts = [];
                    foreach ($conn->IList->I as $info) {
                        $alert = new Alert();
                        $alert->header = trim($info['header']);
                        $alert->description = trim(addslashes($info['text']));
                        array_push($alerts, $alert);
                    }
                    $connection[$i]->alert = $alerts;
                }

                $connection[$i]->departure->delay = $departureDelay;
                $connection[$i]->departure->platform = new Platform();
                $connection[$i]->departure->platform->name = $departurePlatform;
                $connection[$i]->departure->platform->normal = $departurePlatformNormal;

                $connection[$i]->arrival->delay = $arrivalDelay;
                $connection[$i]->arrival->platform = new Platform();
                $connection[$i]->arrival->platform->name = $arrivalPlatform;
                $connection[$i]->arrival->platform->normal = $arrivalPlatformNormal;

                $trains = [];
                $vias = [];
                $directions = [];
                $j = 0;
                $k = 0;
                $connectionindex = 0;
                if (isset($conn->ConSectionList->ConSection)) {
                    foreach ($conn->ConSectionList->ConSection as $connsection) {
                        if (isset($connsection->Journey->JourneyAttributeList->JourneyAttribute)) {
                            foreach ($connsection->Journey->JourneyAttributeList->JourneyAttribute as $att) {
                                if ($att->Attribute['type'] == 'NAME') {
                                    $trains[$j] = str_replace(' ', '', $att->Attribute->AttributeVariant->Text);
                                    $j++;
                                } elseif ($att->Attribute['type'] == 'DIRECTION') {
                                    $__stat = new stdClass();
                                    //This recently changed: only fetch direction name, nothing else.
                                    $__stat->name = str_replace(' [NMBS/SNCB]', '', trim($att->Attribute->AttributeVariant->Text));
                                    $directions[$k] = $__stat;
                                    $k++;
                                }
                            }

                            if ($conn->Overview->Transfers > 0 && strcmp($connsection->Arrival->BasicStop->Station['name'], $conn->Overview->Arrival->BasicStop->Station['name']) != 0) {
                                //current index for the train: j-1
                                $connarray = $conn->ConSectionList->ConSection;

                                $departTime = tools::transformTime($connarray[$connectionindex + 1]->Departure->BasicStop->Dep->Time, $conn->Overview->Date);
                                $departPlatform = trim($connarray[$connectionindex + 1]->Departure->BasicStop->Dep->Platform->Text);

                                $departDelay = tools::transformTime($connarray[$connectionindex + 1]->Departure->BasicStop->StopPrognosis->Dep->Time, $conn->Overview->Date) - $departTime;
                                if ($departDelay < 0) {
                                    $departDelay = 0;
                                }

                                if ($connarray[$connectionindex + 1]->Departure->BasicStop->StopPrognosis->Status == "SCHEDULED" ||
                                    $connarray[$connectionindex + 1]->Departure->BasicStop->StopPrognosis->Status == "PARTIAL_FAILURE_AT_ARR") {
                                    $departcanceled = false;
                                } else {
                                    $departcanceled = true;
                                }

                                $departPlatformNormal = true;
                                if (isset($connarray[$connectionindex+1]->Departure->BasicStop->StopPrognosis->Dep->Platform->Text)) {
                                    $departPlatform = trim($connarray[$connectionindex+1]->Departure->BasicStop->StopPrognosis->Dep->Platform->Text);
                                    $departPlatformNormal = false;
                                }

                                $arrivalTime = tools::transformTime($connsection->Arrival->BasicStop->Arr->Time, $conn->Overview->Date);
                                $arrivalPlatform = trim($connsection->Arrival->BasicStop->Arr->Platform->Text);

                                $arrivalDelay = tools::transformTime($connarray[$connectionindex]->Arrival->BasicStop->StopPrognosis->Arr->Time, $conn->Overview->Date) - $arrivalTime;
                                if ($arrivalDelay < 0) {
                                    $arrivalDelay = 0;
                                }

                                if ($connarray[$connectionindex]->Arrival->BasicStop->StopPrognosis->Status == "SCHEDULED" ||
                                    $connarray[$connectionindex]->Arrival->BasicStop->StopPrognosis->Status == "PARTIAL_FAILURE_AT_DEP") {
                                    $arrivalcanceled = false;
                                } else {
                                    $arrivalcanceled = true;
                                }

                                $arrivalPlatformNormal = true;
                                if (isset($connarray[$connectionindex]->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text)) {
                                    $arrivalPlatform = trim($connarray[$connectionindex]->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text);
                                    $arrivalPlatformNormal = false;
                                }

                                $vias[$connectionindex] = new Via();
                                $vias[$connectionindex]->arrival = new ViaDepartureArrival();
                                $vias[$connectionindex]->arrival->time = $arrivalTime;
                                $vias[$connectionindex]->arrival->delay = $arrivalDelay;
                                $vias[$connectionindex]->arrival->platform = new Platform();
                                $vias[$connectionindex]->arrival->platform->name = $arrivalPlatform;
                                $vias[$connectionindex]->arrival->platform->normal = $arrivalPlatformNormal;
                                $vias[$connectionindex]->arrival->canceled = $arrivalcanceled;
                                $vias[$connectionindex]->departure = new ViaDepartureArrival();
                                $vias[$connectionindex]->departure->time = $departTime;
                                $vias[$connectionindex]->departure->delay = $departDelay;
                                $vias[$connectionindex]->departure->platform = new Platform();
                                $vias[$connectionindex]->departure->platform->name = $departPlatform;
                                $vias[$connectionindex]->departure->platform->normal = $departPlatformNormal;
                                $vias[$connectionindex]->departure->canceled = $departcanceled;
                                $vias[$connectionindex]->timeBetween = $departTime - $arrivalTime;
                                if (isset($directions[$k - 1])) {
                                    $vias[$connectionindex]->direction = $directions[$k - 1];
                                } else {
                                    $vias[$connectionindex]->direction = 'unknown';
                                }
                                $vias[$connectionindex]->vehicle = 'BE.NMBS.'.$trains[$j - 1];
                                $vias[$connectionindex]->station = self::getStationFromHafasDescription($connsection->Arrival->BasicStop->Station['name'], $connsection->Arrival->BasicStop->Station['x'], $connsection->Arrival->BasicStop->Station['y'], $lang);
                                $vias[$connectionindex]->departure->departureConnection = 'http://irail.be/connections/' . substr(basename($vias[$connectionindex]->station->{'@id'}), 2) . '/' . date('Ymd', $departTime) . '/' . substr($vias[$connectionindex]->vehicle, strrpos($vias[$connectionindex]->vehicle, '.') + 1);
                                $connectionindex++;
                            }
                        }
                    }
                    //check if there were vias at all
                    if ($connectionindex != 0) {
                        //if there were vias, add them to the array
                        $connection[$i]->via = $vias;
                    }
                }

                $connection[$i]->departure->vehicle = 'BE.NMBS.'.$trains[0];
                $connection[$i]->departure->departureConnection = 'http://irail.be/connections/' . substr(basename($fromstation->{'@id'}), 2) . '/' . date('Ymd', $connection[$i]->departure->time) . '/' . $trains[0];
                if (isset($directions[0])) {
                    $connection[$i]->departure->direction = $directions[0];
                } else {
                    $connection[$i]->departure->direction = 'unknown';
                }

                $connection[$i]->arrival->vehicle = 'BE.NMBS.'.$trains[count($trains) - 1];
                if (isset($directions[count($directions) - 1])) {
                    $connection[$i]->arrival->direction = $directions[count($directions) - 1];
                } else {
                    $connection[$i]->arrival->direction = 'unknown';
                }

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
                $URI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                if (!is_null($URI)) {
                    $occupancyArr = [];

                    $occupancyConnections[$i]->departure->occupancy->{'@id'} = $URI;
                    $occupancyConnections[$i]->departure->occupancy->name = basename($URI);
                    array_push($occupancyArr, $URI);

                    if (!is_null($occupancyConnections[$i]->via)) {
                        foreach ($occupancyConnections[$i]->via as $key => $via) {
                            if ($key < count($occupancyConnections[$i]->via) - 1) {
                                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($occupancyConnections[$i]->via[$key + 1]->vehicle, "."), 1);
                            } else {
                                $vehicleURI = 'http://irail.be/vehicle/' . substr(strrchr($occupancyConnections[$i]->arrival->vehicle, "."), 1);
                            }

                            $from = $via->station->{'@id'};

                            $URI = OccupancyOperations::getOccupancyURI($vehicleURI, $from, $date);

                            $via->departure->occupancy->{'@id'} = $URI;
                            $via->departure->occupancy->name = basename($URI);
                            array_push($occupancyArr, $URI);
                        }
                    }

                    $URI = OccupancyOperations::getMaxOccupancy($occupancyArr);

                    $occupancyConnections[$i]->occupancy->{'@id'} = $URI;
                    $occupancyConnections[$i]->occupancy->name = basename($URI);
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
