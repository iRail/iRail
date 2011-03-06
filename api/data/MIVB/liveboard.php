<?php
  /** Copyright (C) 2011 by iRail vzw/asbl 
   *
   * This will fetch all liveboarddata for the MIVB. It implements a couple of standard functions implemented by all stations classes:
   *   
   *   * fillDataRoot will fill the entire dataroot with stations
   *
   * @package data/MIVB
   */
include_once("data/MIVB/stations.php");
class liveboard{
     public static function fillDataRoot($dataroot,$request){
//detect if this is an id or a station
	  if(sizeof(explode(".",$request->getStation()))>1){
	       $dataroot->station = stations::getStationFromID($request->getStation(), $request->getLang());
	  }else{
	       $dataroot->station = stations::getStationFromName($request->getStation(), $request->getLang());
	  }
	  if($request->getArrdep() == "ARR"){
	       $xml = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"A");
	       $dataroot->arrival = liveboard::parseData($xml, $request->getLang());
	  }else if($request->getArrdep() == "DEP"){
	       $xml = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"D");
	       $dataroot->departure = liveboard::parseData($xml, $request->getLang());
	  }
	  else{
	       throw new Exception("Not a good timeSel value: try ARR or DEP", 300);
	  }
     }

     private static function fetchData($station,$time,$lang,$arrdep){
	  include "../includes/getUA.php";
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  if($arrdep == "A"){
	       throw new Exception("Not yet implemented. If you really need this function you can try to implement it yourself in http://github.com/iRail/iRail or you can ask on the mailinglist list.iRail.be",500);
	  }
//	  $MIVBMODES = array("M", "N", "T", "B");
	  $s = explode(".",$station->id);
	  $stid = $s[2];
	  $scrapeUrl = "http://stibrt.be/labs/stib/service/getwaitingtimes.php?1=1&iti=1&halt=$stid&lang=$lang";
//	  echo $scrapeUrl . "\n";
	  $post = http_post_data($scrapeUrl, "", $request_options) or die("");
	  return http_parse_message($post)->body;
     }

     private static function parseData($xml, $lang){
                         //<waitingtime><line>5     </line><mode>M     </mode><minutes>6     </minutes><destination>Herrma</destination> </waitingtime>
	  preg_match_all("/<waitingtime>.*?<line>(.*?)<\/line>.*?<mode>(.*?)<\/mode>.*?<minutes>(.*?)<\/minutes>.*?<destination>(.*?)<\/destination>.*?<\/waitingtime>/si", $xml,$matches);
//	  echo $xml . "\n";
//	  var_dump($matches);
	  $nodes = array();
	  for($i=1;$i<sizeof($matches[0]);$i++){
	       $nodes[$i-1] = new DepartureArrival();
	       $nodes[$i-1]->vehicle = "BE.MIVB." . $matches[2][$i] . $matches[1][$i];
	       $nodes[$i-1]->time = date("U") + $matches[3][$i]*60;
	       $nodes[$i-1]->delay = 0;
//	       echo $matches[3][$i] . "   ";
	       $nodes[$i-1]->station = stations::getStationFromName($matches[4][$i], $lang);
//	       echo $nodes[$i-1]->station->name;
	       $nodes[$i-1]->platform = $nodes[$i-1]->station->name;
//	       echo $i-1 . "\n";
	  }
	  return $nodes;
     }
  };

?>