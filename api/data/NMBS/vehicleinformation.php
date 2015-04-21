<?php
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
          // NMBS stationnames in English are in Dutch
          if($lang == 'EN') $lang = 'nl'; // Dutch

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
          $scrapeURL = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/trainsearch.exe/" . $lang . "ld=std&seqnr=1&ident=at.02043113.1429435556&";
          $id = preg_replace("/.*?(\d.*)/smi", "\\1", $id);
          $post_data = "trainname=" . $id . "&start=Zoeken&selectDate=oneday&date=" . date( 'd%2fm%2fY' ) . "&realtimeMode=Show";

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $scrapeURL);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
          $result = curl_exec($ch);
          
          curl_close ($ch);

          return $result;
     }
     

     private static function getData($serverData, $lang, $fast){
	  try{
               $stops = array();
               $html = str_get_html($serverData);
               $nodes = $html->getElementById('tq_trainroute_content_table_alteAnsicht')->getElementByTagName('table')->children;
               
               for($i=1; $i<count($nodes); $i++){
                    $node = $nodes[$i];
                    if(!count($node->attr)) continue; // row with no class-attribute contain no data

                    $delaynodearray = $node->children[2]->find('span');
                    $delay = count($delaynodearray) > 0 ? trim(array_values($delaynodearray[0]->nodes[0]->_)[0]) : "0";

                    $van = array_values($node->children[1]->find('span')[0]->nodes[0]->_)[0]; 
                    
                    if(count($node->children[3]->find('a')))
                         $stationname = array_values($node->children[3]->find('a')[0]->nodes[0]->_)[0];
                    else $stationname = array_values($node->children[3]->nodes[0]->_)[0];

                    $stops[$i-1] = new Stop();
                    $station = new Station();
                    if($fast == "true"){
                         $station->name = $stationname;
                    }else{
                         $station = stations::getStationFromName($stationname,$lang);
                    }
                    $stops[$i-1]->station = $station;
                    $stops[$i-1]->delay = $delay;
                    $stops[$i-1]->time = tools::transformTime("00d" . $van . ":00", date("Ymd"));
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

          $test = $html->getElementById('tq_trainroute_content_table_alteAnsicht');
          if (!is_object($test)) die(""); // catch errors 

          $nodes = $html->getElementById('tq_trainroute_content_table_alteAnsicht')->getElementByTagName('table')->children;

               for($i=1; $i<count($nodes); $i++){
                    $node = $nodes[$i];
                    if(!count($node->attr)) continue; // row with no class-attribute contain no data

                    $station = array_values($node->children[3]->find('a')[0]->nodes[0]->_)[0];
          
          	     $locationX = 0;
          	     $locationY = 0;
          	     if(isset($station)){
          	          $now = stations::getStationFromName($station, $lang);
          	          $locationX = $now->locationX;
          	          $locationY = $now->locationY;
          	     }
          	     $vehicle = new Vehicle();
          	     $vehicle->name = $id;
          	     $vehicle->locationX = $locationX;
          	     $vehicle->locationY = $locationY;
          	     return $vehicle;
               }

          return null;
     }
     
};

     


?>