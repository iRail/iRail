<?php
  /**
   * Template design pattern
   *
   * This is the interface that will get the data from a server and unmarshall it.
   *
   * @author pieterc
   */
ini_set("include_path", ".:DataStructs:api/:../includes");
abstract class Input {
     public $AVAILABLE_LANG = array("EN", "FR", "NL", "DE");
     protected $request;
     public abstract function fetchData(Request $request);
     public abstract function transformData($serverData);

     public function connectToDB(){
	  include("dbConfig.php");
	  try {
	       mysql_pconnect($api_host, $api_user, $api_password);
	       mysql_select_db($api_database);
	  }
	  catch (Exception $e) {
	       throw new Exception("Error connecting to the database.", 3);
	  }
     }

     public function execute(Request $request){
	  $serverData = $this->fetchData($request);
	  return $this->transformData($serverData);
     }

     /**
      *
      * @param <type> $time -> in 00d15:24:00
      * @param <type> $date -> in 20100915
      * @return seconds since the Unix epoch
      *
      */
     protected static function transformTime($time, $date) {
	  date_default_timezone_set("Europe/Brussels");
	  $dayoffset = intval(substr($time,0,2));
	  $hour = intval(substr($time, 3, 2));
	  $minute = intval(substr($time, 6,2));
	  $second = intval(substr($time, 9,2));
	  $year = intval(substr($date, 0,4));
	  $month = intval(substr($date, 4,2));
	  $day = intval(substr($date,6,2));
	  return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
     }
     /**
      * This function transforms the brail formatted timestring and reformats it to seconds
      * @param int $time
      * @return int Duration in seconds
      */
     protected static function transformDuration($time) {
	  $days = intval(substr($time, 0,2));
	  $hour = intval(substr($time, 3, 2));
	  $minute = intval(substr($time, 6,2));
	  $second = intval(substr($time, 9,2));
	  return $days*24*3600 + $hour*3600 + $minute * 60 + $second;
     }

     /**
      * This function will use approximate string matching to determine what station we're looking for
      * @param string $name
      */
     public function getStation($name1) {
	  $this->connectToDB();
	  $name1 = str_ireplace("[b]","", $name1);
	  $name1 = str_ireplace("(nl)","", $name1);
	  $name1 = mysql_escape_string($name1);
	  $name1 = strtoupper($name1);
	  try {
	       $lang = strtoupper($this->request->getLang());
	       $query = "SELECT stations.`ID`, railtime.`RT`, stations.`X`, stations.`Y`, stations.`STD`,stations.`NL`, stations.`FR`, stations.`EN`, stations.`DE`, stations.`ES` FROM stations LEFT JOIN railtime ON railtime.`ID` = stations.`ID` WHERE  '$name1' LIKE `RAILTIMENAME`";
	       $result = mysql_query($query) or die("Could not get stationslist from DB");
	       if(mysql_num_rows($result)>0){
		    $line = mysql_fetch_array($result, MYSQL_ASSOC);
		    $station = new Station($line["ID"], $line["X"], $line["Y"], $line["RT"]);
		    foreach($this->AVAILABLE_LANG as $lang){
			 $station -> addName($lang, $line[$lang]);
		    }
		    return $station;
	       }else{
		    //source it out to hafas!
		    return $this->getStationFromNameWithHafas($name1);
		    
	       }
	  }
	  catch (Exception $e) {
	       throw new Exception("No station for station name found (getStationFromId)", 3);
	  }
     }
/**
 * This function uses hafas to get the right ID if not found
 */
     private function getStationFromNameWithHafas($name){
	  include "getUA.php";
	  $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="iRail API v1.0" lang="EN">
<LocValReq id="stat" maxNr="1">
<ReqLoc match="' . $name . '" type="ST"/>
</LocValReq>
</ReqC>';
	  $post = http_post_data($url, $postdata, $request_options) or die("");
	  $idbody = http_parse_message($post)->body;
	  preg_match_all("/externalId=\"(.*?)\"/si", $idbody, $matches);
	  $id = $matches[1][0];
	  return $this->getStationFromId("BE.NMBS." . $id);
     }

     public function getStationFromId($id) {
	  $this->connectToDB();
	  try {
	       $id = mysql_escape_string($id);
	       $query = "SELECT stations.`ID`, railtime.`RT`, stations.`X`, stations.`Y`, stations.`STD`,stations.`NL`, stations.`FR`, stations.`EN`, stations.`DE`, stations.`ES` FROM stations LEFT JOIN railtime ON railtime.`ID` = stations.`ID` WHERE stations.`ID` LIKE '$id'";
	       $result = mysql_query($query) or die("Could not get stationslist from DB");
	       $line = mysql_fetch_array($result, MYSQL_ASSOC);
	       $station = new Station($line["ID"], $line["X"], $line["Y"], $line["RT"]);
	       foreach($this->AVAILABLE_LANG as $lang){
		    $station -> addName($lang, $line[$lang]);
	       }
	       return $station;
	  }
	  catch (Exception $e) {
	       throw new Exception("No station for station id found (getStationFromId)", 3);
	  }
     }
     
}
?>
