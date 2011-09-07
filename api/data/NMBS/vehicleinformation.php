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
include_once("../includes/simple_html_dom.php");
class vehicleinformation{
     public static function fillDataRoot($dataroot,$request){
	  $lang= $request->getLang();
	  $serverData = vehicleinformation::getServerData($request->getVehicleId(),$lang);
	  $dataroot->vehicle = vehicleinformation::getVehicleData($serverData, $request->getVehicleId(), $lang);
	  $dataroot->stop = array();
	  $dataroot->stop = vehicleinformation::getData($serverData, $lang, $request->getFast());
     }

     private static function getServerData($id,$lang){
	  include_once("../includes/getUA.php");
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  $scrapeURL = "http://www.railtime.be/mobile/HTML/TrainDetail.aspx";
	  $id = preg_replace("/.*?(\d.*)/smi", "\\1", $id);
	  $scrapeURL .= "?l=" . $lang . "&tid=" . $id . "&dt=" . date( 'd%2fm%2fY' );
	  $post = http_post_data($scrapeURL, "", $request_options) or die("");
	  return http_parse_message($post)->body;	  
     }
     

     private static function getData($serverData, $lang, $fast){
	  try{
               $stops = array();
               $html = str_get_html($serverData);
               $nodes = $html->find("tr.rowHeightTraject");
               $i = 0;
               foreach ($nodes as $node) {
                    $row_delay = str_replace("'",'',str_replace('+','',trim($node->children(3)->first_child()->plaintext)));
                    if (isset($row_delay)) {
                         $arr= array();
                         $arr = explode(":",$row_delay);
                         if(isset($arr[1])){
                              $delay = (60*$arr[0] + $arr[1])*60;
                         }else{
                              $delay = $row_delay * 60;
                         }
                    } else {
                         $delay = 0;
                    }
                    $stops[$i] = new Stop();
                    $station = new Station();
                    if($fast == "true"){
                        $station->name = $node->children(1)->first_child()->plaintext;
                    }else{
                        $station = stations::getStationFromRTName($node->children(1)->first_child()->plaintext,$lang);
                    }
                    $stops[$i]->station = $station;
                    $stops[$i]->delay = $delay;
                    $stops[$i]->time = tools::transformTime("00d" . $node->children(2)->first_child()->plaintext . ":00", date("Ymd"));
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
          $html = str_get_html($serverData);
          $nodes = $html->find("td[class*=TrainReperage]");
          if ($nodes) {
              $station = $nodes[0]->parent()->children(1)->first_child()->plaintext;
          }

	  $locationX = 0;
	  $locationY = 0;
	  if(isset($station)){
	       $now = stations::getStationFromRTName($station, $lang);
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