<?php
  /** Copyright (C) 2011 by iRail vzw/asbl
   *
   * fillDataRoot will fill the entire dataroot with a liveboard for a specific station
   *
   * @package data/NS
   */
include_once("data/NS/stations.php");

class Liveboard{

    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot,$request){
//detect if this is an id or a station
	  if(sizeof(explode(".",$request->getStation()))>1){
	       $dataroot->station = stations::getStationFromID($request->getStation(), $request->getLang());
	  }else{
	       $dataroot->station = stations::getStationFromName($request->getStation(), $request->getLang());
	  }
	  if(strtoupper(substr($request->getArrdep(), 0, 1)) == "A"){
	       //  $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"A");
	       // $dataroot->arrival = liveboard::parseData($html, $request->getTime(), $request->getLang());
	       throw new Exception("Not yet implemented",403);
	  }else if(strtoupper(substr($request->getArrdep(), 0, 1)) == "D"){
	       $xml = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"D");
	       $dataroot->departure = liveboard::parseData($xml, $request->getTime(), $request->getLang());
	  }
	  else{
	       throw new Exception("Not a good timeSel value: try ARR or DEP", 300);
	  }
     }

    /**
     * @param $station
     * @param $time
     * @param $lang
     * @param $timeSel
     * @return SimpleXMLElement
     * @throws Exception
     */
    private static function fetchData($station, $time, $lang, $timeSel){
	  //temporal public credentials for the NS API.
	  $url = "http://". urlencode("pieter@appsforghent.be") . ":" . urlencode("fEoQropezniTJRw_5oKhGVlFwm_YWdOgozdMjSAVPLk3M3yZYKEa0A") . "@webservices.ns.nl/ns-api-avt?station=" . $station->name;
	  $r = new HttpRequest($url, HttpRequest::METH_GET);
	  try {
	       $r->send();
	       if ($r->getResponseCode() == 200) {
		    return new SimpleXMLElement($r->getResponseBody());
	       }
	  } catch (HttpException $ex) {
	       throw new Exception("Could not reach NS server", 500);
	  }
     }

    /**
     * @param $xml
     * @param $time
     * @param $lang
     * @return array
     */
    private static function parseData($xml,$time,$lang){
        $nodes = array();
        //var_dump($xml);

        $i = 0;
        for($i = 0; $i < sizeof($xml->VertrekkendeTrein); $i ++) {
	     $dep = $xml->VertrekkendeTrein[$i];
	     $stationNode = stations::getStationFromName($dep->EindBestemming, $lang);
	     $unixtime = strtotime($dep->VertrekTijd);
	     $delay = 0;
	     if(isset($dep->VertrekVertraging)){
		  $delay = ((int)$dep->VertrekVertraging)*60;
	     }
	     $vehicle = "NS.NL." . $dep->TreinSoort;//no ID?
	     $platformNormal = !$dep->VertrekSpoor->wijziging;
	     $vertrekspoor = $dep->VertrekSpoor . " ";
	     $platform = $vertrekspoor;
	     $left = false;

	    $nodes[$i] = new DepartureArrival();
	    $nodes[$i]->delay= $delay;
	    $nodes[$i]->station= $stationNode;
	    $nodes[$i]->time= $unixtime;
	    $nodes[$i]->vehicle = $vehicle;
	    $nodes[$i]->platform = new Platform();
	    $nodes[$i]->platform->name = $platform;
	    $nodes[$i]->platform->normal = $platformNormal;
	    $nodes[$i]->left = $left;
        }
        return $nodes;
     }

};


?>
