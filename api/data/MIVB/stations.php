<?php
  /** Copyright (C) 2011 by iRail vzw/asbl
   *
   * This will fetch all stationdata for the MIVB. It implements a couple of standard functions implemented by all stations classes:
   *
   *   * fillDataRoot will fill the entire dataroot with stations
   *   * getStationFromName will return the right station object for a Name
   *
   * @package data/MIVB
   */
class stations{
     public static function fillDataRoot($dataroot,$request){
	  $dataroot->station = stations::fetchAllStationsFromDB($request->getLang());
     }

     public static function getStationFromLocation($locationX, $locationY, $lang){
	  if($lang == "EN"){
	       $lang="STD";
	  }
	  APICall::connectToDB();
	  mysql_query("SET NAMES utf8");
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $locationX = mysql_real_escape_string($locationX);
	       $locationY = mysql_real_escape_string($locationY);
//It selects the closest station to the given coordinates. It needs to calculate the squared distance and return the smalest of those
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE ((`X`-$locationX)*(`X`-$locationX)+(`Y`-$locationY)*(`Y`-$locationY)) = (SELECT MIN((`X`-$locationX)*(`X`-$locationX)+(`Y`-$locationY)*(`Y`-$locationY)) FROM mivb)";
	       $result = mysql_query($query) || die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
	       $station->name = $line[$lang];
	       $station->standardname = $line["STD"];
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 3);
	  }
	  return $station;
     }

     public static function getStationFromName($name, $lang){
	  if($lang == "EN"){
	       $lang="STD";
	  }
	  APICall::connectToDB();
	  mysql_query("SET NAMES utf8");
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $id = mysql_real_escape_string($name);
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM mivb WHERE `$lang` like '$name'";
	       $result = mysql_query($query) || die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
	       $station->name = $line[$lang];
	       $station->standardname = $line["STD"];
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 500);
	  }
	  if($station->id == ""){
	       throw new Exception("No station found for name", 400);
	  }

	  return $station;
     }


     public static function getStationFromID($id, $lang){
	  if($lang == "EN"){
	       $lang="STD";
	  }
	  APICall::connectToDB();
	  mysql_query("SET NAMES utf8");
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $id = mysql_real_escape_string($id);
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM mivb WHERE `ID` = '$id'";
	       $result = mysql_query($query) || die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
	       $station->name = $line[$lang];
	       $station->standardname = $line["STD"];
	       if($station->id == ""){
		    throw new Exception("Can't find id in db");
	       }
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database - " . $e->getMessage(), 3);
	  }
	  return $station;
     }

     private static function fetchAllStationsFromDB($lang){
	  if($lang == "EN"){
	       $lang="STD";
	  }
	  APICall::connectToDB();
	  mysql_query("SET NAMES utf8");
	  $station = array();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM mivb ORDER BY `$lang`";
	       $result = mysql_query($query) || die("Could not get stationslist from DB");
	       $i = 0;
	       while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		    $station[$i] = new Station();
		    $station[$i]->id = $line["ID"];
		    $station[$i]->locationX = $line["X"];
		    $station[$i]->locationY = $line["Y"];
		    $station[$i]->name = $line[$lang];
		    $station[$i]->standardname = $line["STD"];
		    $i++;
	       }
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 300);
	  }

	  return $station;
     }

     public static function getLinesFromCoordinates($x,$y,$lang){
	  if($lang == "EN"){
	       $lang="STD";
	  }
	  $scrapeUrl = "http://m.mivb.be/closeststops.php?latitude=$y&longitude=$x&lang=$lang";
	  include "../includes/getUA.php";
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );

	  $post = http_post_data($scrapeUrl, "", $request_options) || die("");
	  $body = http_parse_message($post)->body;
	  preg_match("/<ul>(.*?)<\/ul>/si",$body, $match);
	  if(!isset($match[1])){
	       throw new Exception("Could not find lineslist for MIVB", 500);
	  }
	  $list = $match[1];
	  preg_match_all("/<span class=\".*?\">(.*?)<\/span>/si", $list, $matches);
	  $lines = array();
	  $i =0;
	  foreach($m as $matches[1]){
	       $m[$i] = new line();
	       $m[$i]->id = $m;
	       $i++;
	  }
	  return $lines;
     }
  };

class line{
     public $id;
     public $mode;
     public $from;
     public $to;
     public $x;
     public $y;
};

?>
