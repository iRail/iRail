<?php
  /** Copyright (C) 2011 by iRail vzw/asbl 
   *   
   * fillDataRoot will fill the entire dataroot with a liveboard for a specific station
   *
   * @package data/NMBS
   */
include_once("data/NMBS/tools.php");
include_once("data/NMBS/stations.php");
class liveboard{
     public static function fillDataRoot($dataroot,$request){
//detect if this is an id or a station
	  if(sizeof(explode(".",$request->getStation()))>1){
	       $dataroot->station = stations::getStationFromID($request->getStation(), $request->getLang());
	  }else{
	       $dataroot->station = stations::getStationFromName($request->getStation(), $request->getLang());
	  }
	  if($request->getArrdep() == "ARR"){
	       $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"A");
	       $dataroot->arrival = liveboard::parseData($html, $request->getLang());
	  }else if($request->getArrdep() == "DEP"){
	       $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"D");
	       $dataroot->departure = liveboard::parseData($html, $request->getLang());
	  }	       
	  else{
	       throw new Exception("Not a good timeSel value: try ARR or DEP", 300);
	  }
     }

     private static function fetchData($station, $time, $lang, $timeSel){
	  include "../includes/getUA.php";
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  $body = "";
//we want data for 1 hour. But we can only retrieve 15 minutes per request
	  for($i=0;$i<4;$i++){
	       $scrapeUrl = "http://www.railtime.be/mobile/SearchStation.aspx";
	       $scrapeUrl .= "?l=" . $lang . "&tr=". $time . "-15&s=1&sid=" . stations::getRTID($station, $lang) . "&da=" . $timeSel . "&p=2";
	       $post = http_post_data($scrapeUrl, "", $request_options) or die("");
	       $body .= http_parse_message($post)->body;
	       $time = tools::addQuarter($time);
	  }
	  return $body;
	  
     }
  
     private static function parseData($html,$lang){
        preg_match_all("/<tr>(.*?)<\/tr>/ism", $html, $m);
	$nodes = array();
        $i = 0;
        //for each row
        foreach ($m[1] as $tr) {
//
//TODO: remove doubles
//TODO: strip slashes /
            preg_match("/<td valign=\"top\">(.*?)<\/td><td valign=\"top\">(.*?)<\/td>/ism", $tr, $m2);
            //$m2[1] has: time
            //$m2[2] has delay, stationname & platform

            //GET LEFT OR NOT
	    $left = 0;
	    if(preg_match("/color=\"DarkGray\"/ism",$tr,$lll) > 0 ){
		 $left = 1;
	    }

            //GET TIME:
            preg_match("/(\d\d:\d\d)/", $m2[1], $t);
            $time = "00d" . $t[1] . ":00";
            $unixtime = tools::transformTime($time,"20".date("ymd"));

            //GET DELAY
            $delay = 0;
            preg_match("/\+(\d+)'/", $m2[2], $d);
            if(isset($d[1])){
                $delay = $d[1] * 60;
            }
            preg_match("/\+(\d):(\d+)/", $m2[2], $d);
            if(isset($d[1])){
                $delay = $d[1] * 3600 + $d[2]*60;
            }

            //GET STATION
            preg_match("/.*&nbsp;(.*?)&nbsp;<span/",$m2[2],$st);
            //echo $st[1] . "\n";
	    $st = explode("/",$st[1]);
	    $st = trim($st[0]);
            $stationNode = stations::getStationFromRTName(strtoupper($st), $lang);

            //GET VEHICLE AND PLATFORM
            $platform = "";
            $platformNormal = true;
            preg_match("/\[(.*?)(&nbsp;.*?)?\]/",$m2[2],$veh);
            $vehicle = "BE.NMBS." . $veh[1];
            if(isset($veh[2])){
                if(preg_match("/<[bf].*?>(.*?)<\/.*?>/", $veh[2], $p)){
                    $platform = $p[1];
                    $platformNormal = false;
                }else{
                    //echo $veh[2] . "\n";
                    preg_match("/&nbsp;.*?&nbsp;(.*)/", $veh[2], $p2);
                    if(isset($p2[1])){
                        $platform = $p2[1];
                    }
                }
            }
	    $nodes[$i] = new DepartureArrival();
	    $nodes[$i]->delay= $delay;
	    $nodes[$i]->station= $stationNode;
	    $nodes[$i]->time= $unixtime;
	    $nodes[$i]->vehicle = $vehicle;
	    $nodes[$i]->platform = new Platform();
	    $nodes[$i]->platform->name = $platform;
	    $nodes[$i]->platform->normal = $platformNormal;
	    $nodes[$i]->left = $left;
            $i++;
        }
        return $nodes;
     }
};


?>