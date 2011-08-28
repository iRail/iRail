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
	  if(strtoupper(substr($request->getArrdep(), 0, 1)) == "A"){
	       $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"A");
	       $dataroot->arrival = liveboard::parseData($html, $request->getTime(), $request->getLang());
	  }else if(strtoupper(substr($request->getArrdep(), 0, 1)) == "D"){
	       $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"D");
	       $dataroot->departure = liveboard::parseData($html, $request->getTime(), $request->getLang());
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

	       
		$scrapeUrl = "http://www.railtime.be/mobile/HTML/StationDetail.aspx";
		$scrapeUrl .= "?sn=" . urlencode($station->name) . "&sid=" . stations::getRTID($station, $lang) . "&ti=" . urlencode($time) . "&da=" . urlencode($timeSel) . "&l=EN&s=1";
	
	       $post = http_post_data($scrapeUrl, "", $request_options) or die("");
	       $body .= http_parse_message($post)->body;
	       $time = tools::addQuarter($time);
	  }
	  
	  return $body;
	  
     }
  
     private static function parseData($html,$time,$lang){
	  $hour = substr($time, 0,2);
	  
	
        preg_match_all("/<table class=\"StationList\">(.*?)<\/table>/ism", $html, $m);


	$nodes = array();
        $i = 0;
        //for each row
	
        foreach ($m[0] as $table) {
		$left = 0;
		preg_match_all("/<tr class=(.*?)>(.*?)<\/tr>/ism", $table, $m2);
		
		if($m2[1][0] == "rowStation trainLeft"){$left = 1;}
		

		preg_match_all("/<label>(.*?)<\/label>/ism",$m2[2][0],$m3);

		//$m3[1][0] has : time
		//$m3[1][1] has : stationname
		
		preg_match_all("/<label class=\"orange\">(.*?)<\/label>/ism",$m2[2][0],$delay);
		preg_match_all("/<label class=\"bold\">(.*?)<\/label>/ism",$m2[2][0],$platform);
		preg_match_all("/<a class=\"button cmd blue\" href=(.*?)>&gt;<\/a>/ism", $m2[2][0], $id);
		
		
		$delay = $delay[1][0];
		$platform = $platform[1][0];
		$id = $id[1][0];

           

            //GET TIME:
            preg_match("/((\d\d):\d\d)/", $m3[1][0], $t);
            //if it is 23 pm and we fetched data for 1 hour, we may end up with data for the next day and thus we will need to add a day
	    $dayoffset = 0;
	    if($t[2] != 23 && $hour == 23){
		 $dayoffset = 1;
	    }
	    
            $time = "0". $dayoffset . "d" . $t[1] . ":00";

	    $dateparam = date("ymd");
            //day is previous day if time is before 4 O clock (NMBS-ish thing)
	    if(date('G') < 4){
		 $dateparam = date("ymd", strtotime("-1 day"));
	    }
            $unixtime = tools::transformTime($time,"20". $dateparam);

            //GET DELAY
            preg_match("/\+(\d+)'/", $delay, $d);
            if(isset($d[1])){
                $delay = $d[1] * 60;
            }
            preg_match("/\+(\d):(\d+)/", $delay, $d);
            if(isset($d[1])){
                $delay = $d[1] * 3600 + $d[2]*60;
            }

            //GET STATION
	    $st = trim(utf8_encode($m3[1][1]));
	    try{
		 $stationNode = stations::getStationFromRTName(strtoupper($st), $lang);
	    }catch(Exception $e){
//fallback: if no railtime name is available, let's ask HAFAS to solve this issue for us
		 $stationNode = stations::getStationFromName($st, $lang);
	    }	    

            //GET VEHICLE AND PLATFORM

            $platformNormal = true;
	    $veh = explode(";",$id);
	    $veh = explode("=",$veh[2]);
	    
            $vehicle = "BE.NMBS." . str_replace("&amp", "", ($veh[1]));
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
        return liveboard::removeDuplicates($nodes);
     }

/**
 * Small algorithm I wrote:
 * It will remove the duplicates from an array the php way. Since a PHP array will need to recopy everything to be reindexed, I figured this would go faster if we did the deleting when copying.
 */
     private static function removeDuplicates($nodes){
	  $newarray = array();
	  for($i = 0; $i < sizeof($nodes); $i++){
	       $duplicate = false;
	       for($j = 0; $j < $i; $j++){
		    if($nodes[$i]->vehicle == $nodes[$j]->vehicle){
			 $duplicate = true;
			 continue;
		    }
	       }
	       if(!$duplicate){
		    $newarray[sizeof($newarray)] = $nodes[$i];
	       }
	  }
	  return $newarray;
     }
};


?>
