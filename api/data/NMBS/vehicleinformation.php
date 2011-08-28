<?
/** Copyright (C) 2011 by iRail vzw/asbl 
 *
 * This will fetch all vehicledata for the NMBS.
 *   
 *   * fillDataRoot will fill the entire dataroot with vehicleinformation
 *
 * @package data/NMBS
 */
include_once("data/NMBS/tools.php");
include_once("data/NMBS/stations.php");
class vehicleinformation{
     public static function fillDataRoot($dataroot,$request){
	  $lang= $request->getLang();
	  $serverData = vehicleinformation::getServerData($request->getVehicleId(),$lang);
	  $dataroot->vehicle = vehicleinformation::getVehicleData($serverData, $request->getVehicleId(), $lang);
	  $dataroot->stop = array();
	  $dataroot->stop = vehicleinformation::getData($serverData, $lang);
     }

     private static function getServerData($id,$lang){
	  include_once("../includes/getUA.php");
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  $scrapeURL = "http://www.railtime.be/mobile/SearchTrain.aspx";
	  $id = preg_replace("/.*?(\d.*)/smi", "\\1", $id);
	  $scrapeURL .= "?l=" . $lang . "&s=1&tid=" . $id . "&da=D&p=2";
	  $post = http_post_data($scrapeURL, "", $request_options) or die("");
	  return http_parse_message($post)->body;	  
     }
     

     private static function getData($serverData, $lang){
	  try{
	       $stops = array();
	       //BEGIN: O 20:29 +8'&nbsp;<a.*? >(.*?)</a><br>
	       //NORMAL: | 20:50 +9'&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=1265&tr=20:45-60&da=D&p=2">Zele</a><br>
	       //AT: =&gt;22:01&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=318&tr=22:00-60&da=D&p=2">Denderleeuw</a></font><br>
	       //BETWEEN: <font face="Arial">| 10:35</font><font color="Red"> +15'</font><font face="Arial">&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=215&tr=10:45-60&da=D&p=2">Brussel-Centraal</a><br>
	       //        | 10:40<font color="Red"> +15'</font></font>&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=221&tr=10:45-60&da=D&p=2">Brussel-Noord</a><br>
	       //END: O 23:28&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=973&tr=23:15-60&da=D&p=2">Poperinge</a><br>
	       preg_match_all("/(\d\d:\d\d).*?( \+(\d\d?(:?\d\d)?)'?)?&nbsp;<a href=\"\/mobile\/SearchStation.*?>(.*?)<\/a>/smi", $serverData, $matches);
	       $delays = $matches[3];
	       $times = $matches[0];
	       $stationnames = $matches[5];
	       $i = 0;
	       foreach ($stationnames as $st) {
		    if (isset($delays[$i])) {
			 $arr= array();
			 $arr = explode(":",$delays[$i]);
			 if(isset($arr[1])){
			      $delay = (60*$arr[0] + $arr[1])*60;
			 }else{
			      $delay = $delays[$i] * 60;
			 }
		    } else {
			 $delay = 0;
		    }
		    $time = tools::transformTime("00d" . $times[$i] . ":00", date("Ymd"));
		    $stops[$i] = new Stop();
		    $stops[$i]->station = stations::getStationFromRTName($st,$lang);
		    $stops[$i]->delay = $delay;
		    $stops[$i]->time = $time;
		    $i++;
	       }
	       return $stops;
	  }
	  catch(Exception $e){
	       throw new Exception($e->getMessage(), 500);
	  }
     }

     private static function getVehicleData($serverData, $id, $lang){
// determine the location of the vehicle
	  preg_match("/=&gt;(\d\d:\d\d)( \+(\d\d?)')?&nbsp;<a href=\"\/mobile\/SearchStation.*? >(.*?)<\/a>/smi", $serverData, $matches);
	  $locationX = 0;
	  $locationY = 0;
	  if(isset($matches[4])){
	       $now = stations::getStationFromRTName($matches[4], $lang);
	       $locationX = $now -> locationX;
	       $locationY = $now -> locationY;
	  }
	  $vehicle = new Vehicle();
	  $vehicle->name = $id;
	  $vehicle->locationX = $locationX;
	  $vehicle->locationY = $locationY;
	  return $vehicle;
     }
     
};

     


?>