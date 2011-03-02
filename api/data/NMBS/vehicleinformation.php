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
	  $dataroot->vehicle = $request->getVehicleId();
	  $dataroot->stop = array();
	  $dataroot->stop = vehicleinformation::getData($request->getVehicleId(),$request->getLang());
     }

     private static function getData($id,$lang){
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
	  $serverData = http_parse_message($post)->body;

	  $stops = array();
	  //BEGIN: O 20:29 +8'&nbsp;<a.*? >(.*?)</a><br>
	  //NORMAL: | 20:50 +9'&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=1265&tr=20:45-60&da=D&p=2">Zele</a><br>
	  //AT: =&gt;22:01&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=318&tr=22:00-60&da=D&p=2">Denderleeuw</a></font><br>
	  //END: O 23:28&nbsp;<a href="/mobile/SearchStation.aspx?l=NL&s=1&sid=973&tr=23:15-60&da=D&p=2">Poperinge</a><br>
	  preg_match_all("/(\d\d:\d\d)( \+(\d\d?)')?&nbsp;<a href=\"\/mobile\/SearchStation.*?>(.*?)<\/a>/smi", $serverData, $matches);
	  $delays = $matches[3];
	  $times = $matches[0];
	  $stationnames = $matches[4];
	  $i = 0;
	  foreach ($stationnames as $st) {
	       if (isset($delays[$i])) {
		    $delay = $delays[$i] * 60;
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

////TODO: determine the location of the vehicle
//	  preg_match("/=&gt;(\d\d:\d\d)( \+(\d\d?)')?&nbsp;<a href=\"\/mobile\/SearchStation.*? >(.*?)<\/a>/smi", $serverData, $matches);
//	  if(isset($matches[4])){
//	       $now = new Stop($matches[4], "1","1");
//	       $locationX = $now -> getStation() -> getX();
//	       $locationY = $now -> getStation() -> getY();
//	  }else{
//	       $locationX = 0;
//	       $locationY = 0;
//	  }
//
	  return $stops;
     }
};

     


     ?>