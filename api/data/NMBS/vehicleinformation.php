<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl.
 *
 * This will fetch all vehicledata for the NMBS.
 *
 *   * fillDataRoot will fill the entire dataroot with vehicleinformation
 */
include_once 'data/NMBS/tools.php';
include_once 'data/NMBS/stations.php';
include_once '../includes/simple_html_dom.php';
include_once '../includes/getUA.php';
include_once 'occupancy/OccupancyOperations.php';

class vehicleinformation
{
    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot, $request)
    {
        $lang = $request->getLang();
        $date = $request->getDate();

        $nmbsCacheKey = self::getNmbsCacheKey($request->getVehicleId(), $date, $lang);
        $serverData = Tools::getCachedObject($nmbsCacheKey);
        if ($serverData === false) {
            $serverData = self::getServerData($request->getVehicleId(), $date, $lang);
            Tools::setCachedObject($nmbsCacheKey, $serverData);
        }

        $html = str_get_html($serverData);

        // Check if there is a valid result from the belgianrail website
        if (! self::trainDrives($html)) {
            throw new Exception('Route not available.', 404);
        }
        // Check if train splits
        if (self::trainSplits($html)) {
            // Two URLs, fetch serverData from matching URL
            $serverData = self::parseCorrectUrl($html);
            $html = str_get_html($serverData);
        }


        $dataroot->vehicle = self::getVehicleData($html, $request->getVehicleId(), $lang);
        if ($request->getAlerts() && self::getAlerts($html, $request->getFormat())) {
            $dataroot->alert = self::getAlerts($html, $request->getFormat());
        }

        $vehicleOccupancy = OccupancyOperations::getOccupancy($dataroot->vehicle->{'@id'},
            DateTime::createFromFormat('dmy', $date)->format('Ymd'));

        // Use this to check if the MongoDB module is set up. If not, the occupancy score will not be returned
        if (!is_null($vehicleOccupancy)) {
            $vehicleOccupancy = iterator_to_array($vehicleOccupancy);
        }

        $lastStop = null;

        $dataroot->stop = [];
        $dataroot->stop = self::getData($html, $lang, $request->getFast(), $vehicleOccupancy, $date,
            $request->getVehicleId(), $lastStop);

        // When fast=true, this data will not be available
        if (property_exists($lastStop, "locationX")) {
            $dataroot->vehicle->locationX = $lastStop->locationX;
            $dataroot->vehicle->locationY = $lastStop->locationY;
        }
    }

    public static function getNmbsCacheKey($id, $date, $lang)
    {
        return 'NMBSVehicle|' .join('.', [
            $id,
            $date,
            $lang,
        ]);
    }
    /**
     * @param $id
     * @param $lang
     * @return mixed
     */
    private static function getServerData($id, $date, $lang)
    {
        global $irailAgent; // from ../includes/getUA.php

        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => $irailAgent,
        ];
        $scrapeURL = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/trainsearch.exe/'.$lang.'ld=std&seqnr=1&ident=at.02043113.1429435556&';
        $id = preg_replace("/[a-z]+\.[a-z]+\.([a-zA-Z0-9]+)/smi", '\\1', $id);

        $post_data = 'trainname='.$id.'&start=Zoeken&selectDate=oneday&date='.DateTime::createFromFormat('dmy', $date)->format('d%2fm%2fY').'&realtimeMode=Show';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrapeURL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $request_options['timeout']);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    /**
     * @param $html
     * @param $lang
     * @param $fast
     * @return array
     * @throws Exception
     */
    private static function getData($html, $lang, $fast, $occupancyArr, $date, $vehicle, &$laststop)
    {
        $now = new DateTime();
        $requestedDate = DateTime::createFromFormat('dmy', $date);
        $daysBetweenNowAndRequest = $now->diff($requestedDate);
        $isOccupancyDate = true;

        if ($daysBetweenNowAndRequest->d > 1 && $daysBetweenNowAndRequest->invert == 0) {
            $isOccupancyDate = false;
        }

        try {
            $stops = [];
            $nodes = $html->getElementById('tq_trainroute_content_table_alteAnsicht')
                ->getElementByTagName('table')
                ->children;

            $j = 0;

            $previousHour = 0;
            $nextDay = 0;
            $nextDayArrival = 0;
            for ($i = 1; $i < count($nodes); $i++) {
                $node = $nodes[$i];
                if (! count($node->attr)) {
                    continue;
                } // row with no class-attribute contain no data

                // Delay and canceled
                $splitter = '***';
                $delaycontent = preg_replace("/<br\W*?\/>/", $splitter, $node->children[2]);
                $delayelements = explode($splitter, strip_tags($delaycontent));
                //print_r($delayelements);

                $arrivalDelay = trim($delayelements[0]);
                $arrivalCanceled = false;
                if (! $arrivalDelay) {
                    $arrivalDelay = 0;
                } elseif (stripos($arrivalDelay, '+') !== false) {
                    $arrivalDelay = preg_replace('/[^0-9]/', '', $arrivalDelay) * 60;
                } else {
                    $arrivalDelay = 0;
                    $arrivalCanceled = true;
                }

                $departureDelay = trim($delayelements[1]);
                $departureCanceled = false;
                if (! $departureDelay) {
                    $departureDelay = $arrivalDelay ? $arrivalDelay : 0;
                } elseif (stripos($departureDelay, '+') !== false) {
                    $departureDelay = preg_replace('/[^0-9]/', '', $departureDelay) * 60;
                } else {
                    $departureDelay = 0;
                    $departureCanceled = true;
                }

                // Departed
                // Based on timeline images on the NMBS site.
                // A filled timeline, meaning arrived/departed, has an image ending in "reported.png".
                // Example:
                // <img src="/as/hafas-res/img/pearl/realtime_pearl_middle_arr_dep_reported.png" alt="" title="" width="20" height="44">
                if (isset($node->children[0]) && isset($node->children[0]->children[0])) {
                    $departureImgNode = $node->children[0]->children[0];

                    // Check if this element has a src attribute.
                    if (key_exists('src', $departureImgNode->attr) &&
                        strpos($departureImgNode->attr['src'], 'reported.png') !== false) {
                        $departed = 1;
                    } else {
                        // Default to false if we don't have any information. This keeps API output consistent.
                        // (Always include the field)
                        $departed = 0;
                    }
                } else {
                    // Default to false if we don't have any information. This keeps API output consistent.
                    // (Always include the field)
                    $departed = 0;
                }

                if (isset($node->children[2]) && isset($node->children[2]->children[0])) {
                    // This node can be 3 things
                    // - canceled arrival/departure icon
                    // - extra stop icon
                    // - delay span, in case it's normal
                    // We're just checking for the extra stop icon here
                    $isExtraImgNode = $node->children[2]->children[0];

                    if (key_exists('src', $isExtraImgNode->attr) &&
                        strpos($isExtraImgNode->attr['src'], '/as/hafas-res/img/rt_additional_stop.gif') !== false) {
                        $isExtra = 1;
                    } else {
                        // Default to false if we don't have any information. This keeps API output consistent.
                        // (Always include the field)
                        $isExtra = 0;
                    }
                } else {
                    // Default to false if we don't have any information. This keeps API output consistent.
                    // (Always include the field)
                    $isExtra = 0;
                }

                // Time
                $timenodearray = $node->children[1]->find('span');
                $arrivalTime = reset($timenodearray[0]->nodes[0]->_);
                $departureTime = "";

                if (count($nodes[$i]->children[1]->children) == 3) {
                    $departureTime = reset($nodes[$i]->children[1]->children[2]->nodes[0]->_);
                } else {
                    // Handle first and last stop: time, delay and canceled info
                    $departureTime = $arrivalTime;

                    if ($j != 0) {
                        $departureDelay = $arrivalDelay;
                        $departureCanceled = $arrivalCanceled;
                    }
                }
                
                if (count($node->children[3]->find('a'))) {
                    $as = $node->children[3]->find('a');
                    $stationname = trim(reset($as[0]->nodes[0]->_));
                } else {
                    $stationname = trim(reset($node->children[3]->nodes[0]->_));
                }

                // Platform
                // This is not always included, for example BUSxxxx vehicles don't have platforms
                if (count($node->children) > 5) {
                    $platformnodearray = $node->children[5]->find('span');
                    if (count($platformnodearray) > 0) {
                        $normalplatform = 0;
                        $platform = trim(reset($platformnodearray[0]->nodes[0]->_));
                    } else {
                        $normalplatform = 1;
                        $platform = trim(reset($node->children[5]->nodes[0]->_));
                    }

                    if ($platform == "&nbsp;") {
                        $platform = '?'; // Indicate to end user platform is unknown
                    }
                } else {
                    $platform = "?";
                    $normalplatform = 1;
                }

                if (isset($node->children[3]->children[0])) {
                    $link = $node->children[3]->children[0]->{'attr'}['href'];
                    // With capital S
                    if (strpos($link, 'StationId=')) {
                        $nr = substr($link, strpos($link, 'StationId=') + strlen('StationId='));
                    } else {
                        $nr = substr($link, strpos($link, 'stationId=') + strlen('stationId='));
                    }
                    $nr = substr($nr, 0, strlen($nr) - 1); // delete ampersand on the end
                    $stationId = '00'.$nr;
                } else {
                    $stationId = null;
                }

                $station = new Station();
                if ($fast == 'true') {
                    $station->name = $stationname;
                    if ($stationId) {
                        $station->id = "BE.NMBS." . $stationId;
                    }
                } else {
                    // Station ID can be parsed from the station URL
                    if ($stationId) {
                        $station = stations::getStationFromID($stationId, $lang);
                    } else {
                        $station = stations::getStationFromName($stationname, $lang);
                    }
                }
                // The HTML file is ordered chronologically: so once we crossed midnight, we will alway have a next day set to 1.
                if ($previousHour > (int)substr($departureTime, 0, 2)) {
                    $nextDay = 1;
                }
                if ($previousHour > (int)substr($arrivalTime, 0, 2)) {
                    $nextDayArrival = 1;
                }
                $previousHour = (int)substr($departureTime, 0, 2);
                $dateDatetime = DateTime::createFromFormat('dmy', $date);

                $stops[$j] = new Stop();
                $stops[$j]->station = $station;
                $stops[$j]->departureDelay = $departureDelay;
                $stops[$j]->departureCanceled = $departureCanceled;
                $stops[$j]->scheduledDepartureTime = tools::transformTime('0' . $nextDay . 'd'.$departureTime.':00', $dateDatetime->format('Ymd'));
                $stops[$j]->scheduledArrivalTime = tools::transformTime('0' . $nextDayArrival . 'd'.$arrivalTime.':00', $dateDatetime->format('Ymd'));
                $stops[$j]->arrivalDelay = $arrivalDelay;
                $stops[$j]->arrivalCanceled = $arrivalCanceled;

                if ($fast != 'true') {
                    $stops[$j]->departureConnection = 'http://irail.be/connections/' . substr(basename($stops[$j]->station->{'@id'}),
                            2) . '/' . $dateDatetime->format('Ymd') . '/' . substr($vehicle, 8);
                }
                $stops[$j]->platform = new Platform();
                $stops[$j]->platform->name = $platform;
                $stops[$j]->platform->normal = $normalplatform;
                //for backward compatibility
                $stops[$j]->time = tools::transformTime('0' . $nextDay . 'd'.$departureTime.':00', $dateDatetime->format('Ymd'));
                $stops[$j]->delay = $departureDelay;
                $stops[$j]->canceled = $departureCanceled;
                $stops[$j]->left = $departed;
                $stops[$j]->isExtraStop = $isExtra;

                // Store the last station to get vehicle coordinates
                if ($departed) {
                    $laststop = $stops[$j]->station;
                }

                // Check if it is in less than 2 days and MongoDB is available
                if ($fast != 'true' && $isOccupancyDate && isset($occupancyArr)) {
                    // Add occupancy
                    $occupancyOfStationFound = false;
                    $k = 0;

                    while ($k < count($occupancyArr) && !$occupancyOfStationFound) {
                        if ($station->{'@id'} == $occupancyArr[$k]["from"]) {
                            $occupancyURI = OccupancyOperations::NumberToURI($occupancyArr[$k]["occupancy"]);
                            $stops[$j]->occupancy = new \stdClass();
                            $stops[$j]->occupancy->{'@id'} = $occupancyURI;
                            $stops[$j]->occupancy->name = basename($occupancyURI);
                            $occupancyOfStationFound = true;
                        }
                        $k++;
                    }

                    if (!isset($stops[$j]->occupancy)) {
                        $unknown = OccupancyOperations::getUnknown();
                        $stops[$j]->occupancy = new \stdClass();
                        $stops[$j]->occupancy->{'@id'} = $unknown;
                        $stops[$j]->occupancy->name = basename($unknown);
                    }
                }
                
                $j++;
            }

            // When the train hasn't left yet, set location to first station
            if (is_null($laststop)) {
                $laststop = $stops[0]->station;
            }

            return $stops;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * @param $html
     * @return null|Alerts
     * @throws Exception
     */
    private static function getAlerts($html, $format)
    {
        $test = $html->getElementById('tq_trainroute_content_table_alteAnsicht');
        if (! is_object($test)) {
            throw new Exception('Vehicle not found', 500);
        }
        
        $tables = $html->getElementById('tq_trainroute_content_table_alteAnsicht')->getElementsByTagName('table');
        $nodes = $tables[1]->getElementsByTagName('div');

        $alerts = [];

        foreach ($nodes as $alertnode) {
            $bodysplitter = "*#*";
            $alertbody = strip_tags($alertnode, '<strong>, <br>, <a>');
            $alertbody = str_replace('</strong>', $bodysplitter, $alertbody);
            $alertbody = str_replace('<strong>', '', $alertbody);
            $alertelements = explode($bodysplitter, $alertbody);
            $header = preg_replace("/&nbsp;|\s*\(.*?\)\s*/i", '', $alertelements[0]);

            $alert = new Alert();
            $alert->header = trim($header);

            // TODO: verify this code, is there a case where alertelements[1] is set? Maybe this was earlier, and NMBS changed?
            if (count($alertelements) > 1) {
                $alert->description = trim($alertelements[1]);
            } else {
                $alert->description = trim($alertelements[0]);
            }

            // Keep <a> elements, those are valueable
            $alert->description = strip_tags($alert->description, '<a>');

            // Only encode json, since xml can use CDATA. Trim ", since these are added later on.
            //if ($format == 'json') {
            //    $alert->description = trim(json_encode($alert->description), '"');
            //}

            array_push($alerts, $alert);
        }
        
        return $alerts;
    }

    /**
     * @param $html String the HTML received from NMBS
     * @param $id   String the ID of the vehicle in BE.NMBS.XXXXXX format
     * @param $lang
     * @return null|Vehicle
     * @throws Exception
     */
    private static function getVehicleData($html, $id, $lang)
    {
        $vehicle = new Vehicle();
        $vehicle->name = $id;
        $vehicle->locationX = 0;
        $vehicle->locationY = 0;
        $vehicle->shortname = substr($id, 8);
        $vehicle->{'@id'} = 'http://irail.be/vehicle/' . $vehicle->shortname;

        return $vehicle;
    }

    private static function trainSplits($html)
    {
        return ! is_object($html->getElementById('tq_trainroute_content_table_alteAnsicht'));
    }

    private static function trainDrives($html)
    {
        return $html && is_object($html->getElementById('HFSResult')) && is_object($html->getElementById('HFSResult')->getElementByTagName('table'));
    }

    private static function parseCorrectUrl($html)
    {
        $test = $html->getElementById('HFSResult')->getElementByTagName('table');
        if (! is_object($test)) {
            throw new Exception('Vehicle not found', 500);
        } // catch errors

        // Try first url
        $url = $html->getElementById('HFSResult')
            ->getElementByTagName('table')
            ->children[1]->children[0]->children[0]->attr['href'];

        $serverData = self::getServerDataByUrl($url);

        // Check if no other route id in trainname column
        if (self::isOtherTrain($serverData)) {
            // Second url must be the right one
            $url = $html->getElementById('HFSResult')
                ->getElementByTagName('table')
                ->children[2]->children[0]->children[0]->attr['href'];

            $serverData = self::getServerDataByUrl($url);
        }

        return $serverData;
    }

    private static function isOtherTrain($serverData)
    {
        $html = str_get_html($serverData);
        $traindata = $html->getElementById('tq_trainroute_content_table_alteAnsicht');
        return ! is_object($traindata);
    }

    private static function getServerDataByUrl($url)
    {
        global $irailAgent; // from ../includes/getUA.php

        include_once '../includes/getUA.php';
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => $irailAgent,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $request_options['timeout']);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
};
