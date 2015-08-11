<?php
  /** Copyright (C) 2011 by iRail vzw/asbl
   *
   * Author: Pieter Colpaert <pieter aŧ iRail.be>
   *
   * This will fetch all stationdata for the NS. It implements a couple of standard functions implemented by all stations classes:
   *   * fillDataRoot will fill the entire dataroot with stations
   *   * getStationFromName will return the right station object for a Name
   * @package data/NS
   */
class Stations{
     private static $stations = array();

    /**
     * @param $dataroot
     * @param $request
     */
    public static function fillDataRoot($dataroot,$request){
	  $dataroot->station = stations::fetchAllStationsFromDB($request->getLang());
     }

    /**
     * The closest to a certain location
     * @param $locationX
     * @param $locationY
     * @param $lang
     * @return
     */
     public static function getStationFromLocation($locationX, $locationY, $lang){
	  stations::fetchAllStationsFromDB("EN");
	  $bestindex= 0;
	  $bestdistance = 99999;
	  $i =0;
	  foreach(stations::$stations as $station){
	       $dist = stations::distance($locationX,$station->locationX,$locationY, $station->locationY);
	       if($dist < $bestdistance){
		    $bestdistance = $dist;
		    $bestindex = $i;
	       }
	       $i++;
	  }
	  return stations::$stations[$i];
     }

    /**
     * @param $x1
     * @param $x2
     * @param $y1
     * @param $y2
     * @return int
     */
    private static function distance($x1,$x2,$y1,$y2){
	  $R = 6371; // km
	  $dY = deg2rad($y2-$y1);
	  $dX = deg2rad($x2-$x1);
	  $a = sin($dY/2) * sin($dY/2) + cos(deg2rad($y1)) * cos(deg2rad($y2)) *sin($dX/2) * sin($dY/2);
	  $c = 2 * atan2(sqrt($a), sqrt(1-$a));
	  return $R * $c;
     }

    /**
     * @param $id
     * @param $lang
     * @return mixed
     * @throws Exception
     */
    public static function getStationFromID($id, $lang){
	  stations::fetchAllStationsFromDB("EN");
	  $i = 0;
	  while($i < sizeof(stations::$stations) && stations::$stations[$i]->id != $id){
	       $i++;
	  }
	  if(stations::$stations[$i]->id == $id){
	       return stations::$stations[$i];
	  }
	  throw new Exception("ID not found", 404);
     }

    /**
     * @param $name
     * @param $lang
     * @return null
     */
    public static function getStationFromName($name, $lang){
	  stations::fetchAllStationsFromDB("EN");
	  $i = 0;
	  while($i < sizeof(stations::$stations) && stations::$stations[$i]->name != $name){
	       $i++;
	  }
	  if(stations::$stations[$i]->name == $name){
	       return stations::$stations[$i];
	  }
	  //otherwise search for other method: Hafas?
	  return null;
     }

    /**
     * @param $lang
     * @return array
     */
    private static function fetchAllStationsFromDB($lang){
//<station locationX="4.323973" locationY="52.081261" id="NL.NS.gvc" standardname="'s-Gravenhage">'s-Gravenhage</station>
	  if(sizeof(stations::$stations)==0){
	       $allstations = file_get_contents("data/NS/stations.xml",true);
	       preg_match_all("/<station locationX=\"(.*?)\" locationY=\"(.*?)\" id=\"(.*?)\" standardname=\"(.*?)\">(.*?)<\/station>/smi", $allstations, $matches);
	       for($i =0; $i < sizeof($matches[0]); $i++){
		    stations::$stations[$i] = new Station();
		    stations::$stations[$i]->locationX = $matches[1][$i];
		    stations::$stations[$i]->locationY = $matches[2][$i];
		    stations::$stations[$i]->id = $matches[3][$i];
		    stations::$stations[$i]->standardname = $matches[4][$i];
		    stations::$stations[$i]->name = $matches[5][$i];
	       }
	  }
	  return stations::$stations;
     }
};
