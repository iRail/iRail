<?php
  /** Copyright (C) 2011 by iRail vzw/asbl 
   *
   * This will fetch all stationdata for the NMBS. It implements a couple of standard functions implemented by all stations classes:
   *   
   *   * fillDataRoot will fill the entire dataroot with stations
   *   * getStationFromName will return the right station object for a Name
   *
   * @package data/NMBS
   */
class stations
{
     public static function fillDataRoot($dataroot,$request){
	  $dataroot->station = stations::fetchAllStationsFromDB($request->getLang());
     }

     public static function getStationFromLocation($locationX, $locationY, $lang){
	  APICall::connectToDB();
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $locationX = mysql_real_escape_string($locationX);
	       $locationY = mysql_real_escape_string($locationY);
//It selects the closest station to the given coordinates. It needs to calculate the squared distance and return the smalest of those
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE ((`X`-$locationX)*(`X`-$locationX)+(`Y`-$locationY)*(`Y`-$locationY)) = (SELECT MIN((`X`-$locationX)*(`X`-$locationX)+(`Y`-$locationY)*(`Y`-$locationY)) FROM stations)";
	       $result = mysql_query($query) or die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
	       if($line[$lang] != ""){
		    $station->name = utf8_encode($line[$lang]);
	       }else{
		    $station->name = utf8_encode($line["STD"]);	    
	       }
	       
	       $station->standardname = utf8_encode($line["STD"]);
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 3);
	  }
	  return $station;	  
     }

     public static function getStationFromID($id, $lang){
	  APICall::connectToDB();
          $idarray = explode(".",$id);
	  if(sizeof($idarray) > 0){
		$id=$idarray[2];
	  }
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $id = mysql_real_escape_string($id);
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE `ID` = '$id'";
	       $result = mysql_query($query) or die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
	       if($line[$lang] != ""){
		    $station->name = utf8_encode($line[$lang]);
	       }else{
		    $station->name = utf8_encode($line["STD"]);	    
	       }
	       $station->standardname = utf8_encode($line["STD"]);
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 300);
	  }
	  return $station;
     }

     public static function getStationFromName($name, $lang){
//We can do a couple of things here: 
// * Match the name with something in the DB
// * Give the name to hafas so that it returns us an ID which we can reuse - Doesn't work for external stations
// * Do a hybrid mode
// * match from location
// * match railtime name

//Let's go wih the hafas solution and get the location from it

//fallback for wrong hafas information

          $name = urldecode($name);
          $name = str_ireplace(" ","-",$name);
	  $name = str_ireplace("south", "zuid", $name);
	  $name = str_ireplace("north", "noord", $name);
	  

	  include "../includes/getUA.php";
	
$url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/extxml.exe";
//  $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="iRail API v1.0" lang="'. $lang .'">
<LocValReq id="stat1" maxNr="1">
<ReqLoc match="' . $name . '" type="ST"/>
</LocValReq>
</ReqC>';
	  $post = http_post_data($url, $postdata, $request_options) or die("");
	  $idbody = http_parse_message($post)->body;
	  preg_match("/x=\"(.*?)\".*?y=\"(.*?)\"/si", $idbody, $matches);
	  $x = $matches[1];
	  $y = $matches[2];
	  preg_match("/(.)(.*)/",$x, $m);
	  $x= $m[1] . ".". $m[2];
	  preg_match("/(..)(.*)/",$y, $m);
	
	  $y = $m[1] . ".". $m[2];
          $sss = stations::getStationFromLocation($x,$y,$lang);
          preg_match("/externalStationNr=\"(.*?)\"/si", $idbody,$hafasid);
          $sss->setHID($hafasid[1]);
          return $sss;
     }

     public static function getStationFromRTName($name,$lang){
	  APICall::connectToDB();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $name = mysql_real_escape_string($name);
	       $query = "SELECT stations.`ID`,stations.`X`, stations.`Y`, stations.`STD`,stations.`$lang` FROM stations RIGHT JOIN railtime ON railtime.`ID` = stations.`ID` WHERE railtime.`RAILTIMENAME` = '$name'";
	       $result = mysql_query($query) or die("Could not get stationslist from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station  = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
	       if($line[$lang] != ""){
		    $station->name = utf8_encode($line[$lang]);
	       }else{
		    $station->name = utf8_encode($line["STD"]);	    
	       }
	       $station->standardname = utf8_encode($line["STD"]);
	       if($station->id == ""){
		    throw new Exception("doesn't matter what's in here. It doesn't get parsed", 0);
	       }
	       
	  }
	  catch (Exception $e) {
	       //no station found, let's try a HAFAS lookup as last resort
	       return stations::getStationFromName($name,$lang);
	  }
	  return $station;
     }

     public static function getRTID($station, $lang){
	  APICall::connectToDB();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $station->id = mysql_real_escape_string($station->id);
	       $query = "SELECT `RT`, `RAILTIMENAME` FROM railtime WHERE `ID` = '$station->id'";
	       $result = mysql_query($query) or die("Could not get stationslist from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
               $o = new stdClass();
               $o->rtid = $line["RT"];
               $o->rtname = utf8_encode($line["RAILTIMENAME"]);
	       return $o;
	  }catch(Exception $e){
	       throw new Exception("error getting RT ID", 3);
	  }
     }
     
     
     private static function fetchAllStationsFromDB($lang){
	  APICall::connectToDB();
	  $station = array();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE `ID` LIKE 'BE.NMBS.%' ORDER BY `$lang`";	       
	       $result = mysql_query($query) or die("Could not get stationslist from DB");
	       $i = 0;
	       while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		    $station[$i] = new Station();
		    $station[$i]->id = $line["ID"];
		    $station[$i]->locationX = $line["X"];
		    $station[$i]->locationY = $line["Y"];
		    if($line[$lang] != ""){
                        $station[$i]->name = utf8_encode($line[$lang]);
		    }else{
                        $station[$i]->name = utf8_encode($line["STD"]);	    
		    }
		    $station[$i]->standardname = utf8_encode($line["STD"]);
		    $i++;
	       }
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 3);
	  }
	  return $station;
     }
};

?>
