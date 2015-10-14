<?php
/** 
 * Copyright (C) 2011 by iRail vzw/asbl 
 * Copyright (C) 2015 by Open Knowledge Belgium vzw/asbl 
 *
 * This will fetch all vehicledata for the NMBS.
 *   
 *   * fillDataRoot will fill the entire dataroot with vehicleinformation
 *
 * @package data/NMBS
 */

include_once("data/NMBS/tools.php");
include_once("data/NMBS/stations.php");
include_once("../includes/simple_html_dom.php");
include_once("../includes/getUA.php");
class vehicleinformation{

    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot,$request){
        $lang= $request->getLang();

        $serverData = vehicleinformation::getServerData($request->getVehicleId(),$lang);

        // Check if train splits
        if (vehicleinformation::trainSplits($serverData)) {
            // Two URL's, fetch serverData from matching URL
            $serverData = vehicleinformation::parseCorrectUrl($serverData);
        }

        $dataroot->vehicle = vehicleinformation::getVehicleData($serverData, $request->getVehicleId(), $lang);
        $dataroot->stop = [];
        $dataroot->stop = vehicleinformation::getData($serverData, $lang, $request->getFast());
    }

    /**
     * @param $id
     * @param $lang
     * @return mixed
     */
    private static function getServerData($id,$lang){
        global $irailAgent; // from ../includes/getUA.php

        $request_options = [
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent,
        ];
        $scrapeURL = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/trainsearch.exe/" . $lang . "ld=std&seqnr=1&ident=at.02043113.1429435556&";
        $id = preg_replace("/[a-z]+\.[a-z]+\.([a-zA-Z0-9]+)/smi", "\\1", $id);

        $post_data = "trainname=" . $id . "&start=Zoeken&selectDate=oneday&date=" . date( 'd%2fm%2fY' ) . "&realtimeMode=Show";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrapeURL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $request_options["referer"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $request_options["timeout"]);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options["useragent"]);
        $result = curl_exec($ch);

        curl_close ($ch);
        return $result;
    }


    /**
     * @param $serverData
     * @param $lang
     * @param $fast
     * @return array
     * @throws Exception
     */
    private static function getData($serverData, $lang, $fast){
        try {
            $stops = [];
            $html = str_get_html($serverData);

            $nodes = $html->getElementById('tq_trainroute_content_table_alteAnsicht')
                ->getElementByTagName('table')
                ->children;
            
            
            $j = 0;
            for ($i=1; $i < count($nodes); $i++) {
                $node = $nodes[$i];
                if (!count($node->attr)) continue; // row with no class-attribute contain no data

                $delaynodearray = $node->children[2]->find('span');
                $delay = count($delaynodearray) > 0 ? trim(reset($delaynodearray[0]->nodes[0]->_)) : "0";
                $delayseconds = preg_replace("/[^0-9]/", '', $delay)*60;

                $spans = $node->children[1]->find('span');
                $arriveTime = reset($spans[0]->nodes[0]->_);                    
                $departureTime = count($nodes[$i]->children[1]->children) == 3 ? reset($nodes[$i]->children[1]->children[0]->nodes[0]->_) : $arriveTime;  

                if (count($node->children[3]->find('a'))) {
                    $as = $node->children[3]->find('a');
                    $stationname = reset($as[0]->nodes[0]->_);
                }
                    
                else $stationname = reset($node->children[3]->nodes[0]->_);

                $stops[$j] = new Stop();
                $station = new Station();
                if ($fast == "true"){
                    $station->name = $stationname;
                } else {
                    $station = stations::getStationFromName($stationname,$lang);
                }
                $stops[$j]->station = $station;
                $stops[$j]->delay = $delayseconds;
                $stops[$j]->time = tools::transformTime("00d" . $departureTime . ":00", date("Ymd"));

                $j++;
            }

            return $stops;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500);
        }
    }

    /**
     * @param $serverData
     * @param $id
     * @param $lang
     * @return null|Vehicle
     * @throws Exception
     */
    private static function getVehicleData($serverData, $id, $lang){
        // determine the location of the vehicle
        $html = str_get_html($serverData);

        $test = $html->getElementById('tq_trainroute_content_table_alteAnsicht');
        if (!is_object($test))
            throw new Exception("Vehicle not found", 1); // catch errors

        $nodes = $html->getElementById('tq_trainroute_content_table_alteAnsicht')->getElementByTagName('table')->children;

        for($i=1; $i<count($nodes); $i++){
            $node = $nodes[$i];
            if (!count($node->attr)) continue; // row with no class-attribute contain no data

            if (count($node->children[3]->find('a'))) {
                $as = $node->children[3]->find('a');
                $stationname = reset($as[0]->nodes[0]->_);
            } else {
                // Foreign station, no anchorlink
                $stationname = reset($node->children[3]->nodes[0]->_);
            }

            $locationX = 0;
            $locationY = 0;
            if (isset($station)){
                $now = stations::getStationFromName($station, $lang);
                $locationX = $now->locationX;
                $locationY = $now->locationY;
            }
            $vehicle = new Vehicle();
            $vehicle->name = $id;
            $vehicle->locationX = $locationX;
            $vehicle->locationY = $locationY;
            return $vehicle;
        }

        return null;
    }

    private static function trainSplits($serverData) {
        return is_object(str_get_html($serverData)->getElementById('HFSResult')) && !is_object(str_get_html($serverData)->getElementById('tq_trainroute_content_table_alteAnsicht'));
    }

    private static function parseCorrectUrl($serverData) {
        $html = str_get_html($serverData);

        $test = $html->getElementById('HFSResult')->getElementByTagName('table');
        if (!is_object($test))
            throw new Exception("Vehicle not found", 1); // catch errors

        // Try first url
        $url = $html->getElementById('HFSResult')
            ->getElementByTagName('table')
            ->children[1]->children[0]->children[0]->attr['href'];

        $serverData = vehicleinformation::getServerDataByUrl($url);

        // Check if no other route id in trainname column
        if (vehicleinformation::isOtherTrain($serverData)) {
            // Second url must be the right one
            $url = $html->getElementById('HFSResult')
                ->getElementByTagName('table')
                ->children[2]->children[0]->children[0]->attr['href'];

            $serverData = vehicleinformation::getServerDataByUrl($url);
        }

        return $serverData;
    }

    private static function isOtherTrain($serverData) {
        $html = str_get_html($serverData);
        $originalTrainname = null;

        $nodes = $html->getElementById('tq_trainroute_content_table_alteAnsicht')
            ->getElementByTagName('table')
            ->children;

        for ($i=1; $i < count($nodes); $i++) {
            $node = $nodes[$i];
            if (!count($node->attr)) continue; // row with no class-attribute contain no data

            $trainname = str_replace(' ', '', reset($node->children[4]->nodes[0]->_));
            if (!is_object($originalTrainname)) {
                $originalTrainname = $trainname;
            } else if ($trainname != '&nbsp;' && $trainname != $originalTrainname) {
                // This URL returns route of the other train
                return true;
            }
        }

        return false;
    }

    private static function getServerDataByUrl($url) {
        global $irailAgent; // from ../includes/getUA.php

        include_once("../includes/getUA.php");
        $request_options = [
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $request_options["referer"]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $request_options["timeout"]);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options["useragent"]);
        $result = curl_exec($ch);

        curl_close ($ch);
        return $result;
    }
};
