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
class Stations
{
    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot,$request){
	  $dataroot->station = stations::fetchAllStationsFromDB($request->getLang());
     }

    /**
     * @param $locationX
     * @param $locationY
     * @param $lang
     * @return Station
     * @throws Exception
     */
    public static function getStationFromLocation($locationX, $locationY, $lang){
	  APICall::connectToDB();
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $locationX = mysql_real_escape_string($locationX);
	       $locationY = mysql_real_escape_string($locationY);
               //It selects the closest station to the given coordinates. It needs to calculate the squared distance and return the smalest of those
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE ((`X`-$locationX)*(`X`-$locationX)+(`Y`-$locationY)*(`Y`-$locationY)) = (SELECT MIN((`X`-$locationX)*(`X`-$locationX)+(`Y`-$locationY)*(`Y`-$locationY)) FROM stations)";
	       $result = mysql_query($query) || die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
               $id = str_replace("BE.NMBS.", "",$station->id);
               $station->{"@id"} = "http://irail.be/stations/NMBS/" . $id;

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

    /**
     * @param $id
     * @param $lang
     * @return Station
     * @throws Exception
     */
    public static function getStationFromID($id, $lang){
	  APICall::connectToDB();
          $idarray = explode(".",$id);
	  if(sizeof($idarray) > 1){
		$id=$idarray[2];
	  }
          $id = "BE.NMBS." . $id;
	  $station;
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $id = mysql_real_escape_string($id);
               //e.g., SELECT `ID`,`X`, `Y`, `STD`,`EN` FROM stations WHERE `ID` = 'BE.NMBS.008866530'
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE `ID` = '$id'";
	       $result = mysql_query($query) || die("Could not get station from coordinates from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
               if ($line) {

                   $station = new Station();
                   $station->id = $line["ID"];
                   $station->locationX = $line["X"];
                   $station->locationY = $line["Y"];
                   $id = str_replace("BE.NMBS.", "",$station->id);
                   $station->{"@id"} = "http://irail.be/stations/NMBS/" . $id;
                   if($line[$lang] != ""){
                       $station->name = utf8_encode($line[$lang]);
                   }else{
                       $station->name = utf8_encode($line["STD"]);
                   }
                   $station->standardname = utf8_encode($line["STD"]);
               } else {
                   throw new Exception("Error reading station BE.NMBS." . $id ." from the database.", 300);
               }
	  }
	  catch (Exception $e) {
	       throw new Exception("Error reading from the database.", 300);
	  }
	  return $station;
     }

    /**
     * Gets an appropriate station from the new iRail API
     *
     * @param $name
     * @param $lang
     * @return Station
     * @throws Exception
     */
     public static function getStationFromName($name, $lang){
          //first check if it wasn't by any chance an id
          if(substr($name,0,1) == "0" || substr($name,0,7) == "BE.NMBS"){
              $id = $name;
              $idarray = explode(".",$id);
              if(sizeof($idarray) > 1){
                  $id=$idarray[2];
              }
              $stationresult = stations::getStationFromID($id,$lang);
              $stationresult->setHID($id);
              return $stationresult;
          }
          $name = html_entity_decode($name, ENT_COMPAT | ENT_HTML401, "UTF-8");
          $name = preg_replace("/[ ]?\([a-zA-Z]+\)/","",$name);
          $name = str_replace(" [NMBS/SNCB]","",$name);
          $name = explode("/",$name);
          $name = trim($name[0]);
          $name = urlencode($name);
          $url = "https://irail.be/stations/NMBS?q=" . $name;
          $post = http_get($url) || die("iRail down");
	  $stationsgraph = json_decode(http_parse_message($post)->body);
          if(!isset($stationsgraph->{'@graph'}[0])){
              // die("No station found for " . urldecode($name));
          		return "";
          }
          $station = $stationsgraph->{'@graph'}[0];

          //or find exact match using ugly breaks and strlen
          foreach($stationsgraph->{'@graph'} as $stationitem) {
              if (strlen($stationitem->name) === strlen($name)) {
                  $station = $stationitem;
                  break;
              } else if (isset($stationitem->alternative) && is_array($stationitem->alternative)) {
                  foreach ($stationitem->alternative as $alt) {
                      if(strlen($alt->{"@value"}) === strlen($name)) {
                          $station = $stationitem;
                          break;
                      }
                  }
              } elseif (isset($stationitem->alternative) && strlen($stationitem->alternative->{"@value"}) === strlen($name)){
                  $station = $stationitem;
                  break;
              }
          }
          $x = $station->longitude;
          $y = $station->latitude;
          //sadly, our old API only works with the IDs stored in our database, so we're going to match the longitude latitude and get them from there.
          $stationresult = stations::getStationFromLocation($x,$y,$lang);
          $stationresult->setHID(str_replace("BE.NMBS.","",$stationresult->id));
          return $stationresult;
     }

    /**
     * @param $name
     * @param $lang
     * @return Station
     * @throws Exception
     */
    public static function getStationFromRTName($name,$lang){
	  APICall::connectToDB();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $name = mysql_real_escape_string($name);
	       $query = "SELECT stations.`ID`,stations.`X`, stations.`Y`, stations.`STD`,stations.`$lang` FROM stations WHERE stations.`$lang` = '$name'";
	       $result = mysql_query($query) || die("Could not get stationslist from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station  = new Station();
	       $station->id = $line["ID"];
	       $station->locationX = $line["X"];
	       $station->locationY = $line["Y"];
               $id = str_replace("BE.NMBS.", "",$station->id);
               $station->{"@id"} = "http://irail.be/stations/NMBS/" . $id;
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

    /**
     * @param $station
     * @param $lang
     * @return stdClass
     * @throws Exception
     */
    public static function getRTID($station, $lang){
	  APICall::connectToDB();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $station->id = mysql_real_escape_string($station->id);
	       $query = "SELECT `RT`, `RAILTIMENAME` FROM railtime WHERE `ID` = '$station->id'";
	       $result = mysql_query($query) || die("Could not get stationslist from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
               $o = new stdClass();
               $o->rtid = $line["RT"];
               $o->rtname = utf8_encode($line["RAILTIMENAME"]);
	       return $o;
	  }catch(Exception $e){
	       throw new Exception("error getting RT ID", 3);
	  }
     }


    /**
     * @param $lang
     * @return array
     * @throws Exception
     */
    private static function fetchAllStationsFromDB($lang){
	  APICall::connectToDB();
	  $station = array();
	  try {
	       $lang = mysql_real_escape_string(strtoupper($lang));
	       $query = "SELECT `ID`,`X`, `Y`, `STD`,`$lang` FROM stations WHERE `ID` LIKE 'BE.NMBS.%' ORDER BY `$lang`";
	       $result = mysql_query($query) || die("Could not get stationslist from DB");
	       $i = 0;
	       while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		    $station[$i] = new Station();
		    $station[$i]->id = $line["ID"];
		    $station[$i]->locationX = $line["X"];
		    $station[$i]->locationY = $line["Y"];
                    $id = str_replace("BE.NMBS.", "",$station[$i]->id);
                    $station[$i]->{"@id"} = "http://irail.be/stations/NMBS/" . $id;
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
