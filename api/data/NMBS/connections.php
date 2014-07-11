<?php
  /** Copyright (C) 2011 by iRail vzw/asbl 
   *
   * This will return information about 1 specific route for the NMBS.
   *   
   * fillDataRoot will fill the entire dataroot with connections
   *
   * @package data/NMBS
   */
include_once("data/NMBS/tools.php");
include_once("data/NMBS/stations.php");
class connections{
     public static function fillDataRoot($dataroot,$request){
//detect whether from was an id and change from accordingly
	  $from = $request->getFrom();
	  if(sizeof(explode(".",$request->getFrom()))>1){
	       $from = stations::getStationFromID($request->getFrom(), $request->getLang());
	       $from = $from->name;
	  }
	  $to = $request->getTo();
	  if(sizeof(explode(".",$request->getTo()))>1){
	       $to = stations::getStationFromID($request->getTo(), $request->getLang());
	       $to = $to->name;
	  }	  
	  $dataroot->connection = connections::scrapeConnections($from, $to,$request->getTime(), $request->getDate(),$request->getResults(),$request->getLang(), $request->getFast() , $request->getTimeSel(), $request->getTypeOfTransport());
     }

     private static function scrapeConnections($from,$to, $time, $date, $results,$lang, $fast,$timeSel ="depart", $typeOfTransport = "trains"){
	  $ids = connections::getHafasIDsFromNames($from,$to,$lang);
	  $xml = connections::requestHafasXml($ids[0],$ids[1],$lang,$time,$date,$results,$timeSel,$typeOfTransport);
	  return connections::parseHafasXml($xml, $lang, $fast);
     }

/**
 * This function scrapes the ID from the HAFAS system. Since hafas id's will be requested in pairs, it also returns 2 id's and asks for 2 names
 */
     private static function getHafasIDsFromNames($name1,$name2,$lang){
	  include "../includes/getUA.php";
	  //Hard fix
	  $name1 = str_ireplace("north", "noord", $name1);
	  $name2 = str_ireplace("north", "noord", $name2);
	  
	  $name1 = str_ireplace("south", "zuid", $name1);
	  $name2 = str_ireplace("south", "zuid", $name2);
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
<ReqLoc match="' . $name1 . '" type="ST"/>
</LocValReq>
<LocValReq id="stat2" maxNr="1">
<ReqLoc match="' . $name2 . '" type="ST"/>
</LocValReq>
</ReqC>';
	  $post = http_post_data($url, $postdata, $request_options) or die("");
	  $idbody = http_parse_message($post)->body;
	  preg_match_all("/externalId=\"(.*?)\"/si", $idbody, $matches);
	  $id = $matches[1]; // this is an array of 2 id's
	  return $id;
     }

     private static function requestHafasXml($idfrom,$idto,$lang, $time, $date, $results, $timeSel, $typeOfTransport){
	  include "../includes/getUA.php";
	  $url = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/extxml.exe";
//  $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
	  $request_options = array(
	       "referer" => "http://api.irail.be/",
	       "timeout" => "30",
	       "useragent" => $irailAgent,
	       );
	  if($typeOfTransport == "trains"){
	       $trainsonly = "1111111000000000";
	  }else if($typeOfTransport == "all"){
	       $trainsonly = "1111111111111111";     
	  }else{
	       $trainsonly = "1111111000000000";
	  }

	  if ($timeSel == "depart") {
	       $timeSel = 0;
	  } else if ($timeSel == "arrive") {
	       $timeSel = 1;
	  }else {
	       $timeSel = 1;
	  }
	  
	  //now we're going to get the real data
	  $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="iRail" lang="' . $lang . '">
<ConReq>
<Start min="0">
<Station externalId="' . $idfrom . '" distance="0">
</Station>
<Prod prod="' . $trainsonly . '">
</Prod>
</Start>
<Dest min="0">
<Station externalId="' . $idto . '" distance="0">
</Station>
</Dest>
<Via>
</Via>
<ReqT time="' . $time . '" date="' . $date . '" a="' . $timeSel . '">
</ReqT>
<RFlags b="' . $results * $timeSel . '" f="' . $results * -($timeSel - 1) . '">
</RFlags>
<GISParameters>
<Front>
</Front>
<Back>
</Back>
</GISParameters>
</ConReq>
</ReqC>';
	 $post = http_post_data($url, $postdata, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
	  return http_parse_message($post)->body;
     }

  

     public static function parseHafasXml($serverData, $lang, $fast) {
          $xml = new SimpleXMLElement($serverData);
	  $connection = array();
	  $i = 0;
	  //DEBUG:
//echo $serverData ;
	  if (isset($xml->ConRes->ConnectionList->Connection)) {
//get stations from & to once for all connections
	       $fromstation = connections::getStationFromHafasLocation($xml->ConRes->ConnectionList->Connection[0]->Overview->Departure->BasicStop->Station['x'],$xml->ConRes->ConnectionList->Connection[0]->Overview->Departure->BasicStop->Station['y'], $lang);
	       $tostation = connections::getStationFromHafasLocation($xml->ConRes->ConnectionList->Connection[0]->Overview->Arrival->BasicStop->Station['x'],$xml->ConRes->ConnectionList->Connection[0]->Overview->Arrival->BasicStop->Station['y'], $lang);
	       foreach ($xml->ConRes->ConnectionList->Connection as $conn) {
		    $connection[$i] = new Connection();
		    $connection[$i]->departure = new DepartureArrival();
		    $connection[$i]->arrival = new DepartureArrival();
		    $connection[$i]->duration = tools::transformDuration($conn->Overview->Duration->Time);
		    $connection[$i]->departure->station = $fromstation;
		    $connection[$i]->departure->time = tools::transformTime($conn->Overview->Departure->BasicStop->Dep->Time, $conn->Overview->Date);
		    $connection[$i]->departure->platform = new Platform();
		    $connection[$i]->departure->direction = (trim($conn->Overview->Departure->BasicStop->Dep->Platform->Text));
		    $connection[$i]->departure->platform->name = trim($conn->Overview->Departure->BasicStop->Dep->Platform->Text);
		    $connection[$i]->arrival->time = tools::transformTime($conn->Overview->Arrival->BasicStop->Arr->Time, $conn->Overview->Date);
		    $connection[$i]->arrival->platform = new Platform();
		    $connection[$i]->arrival->platform->name = trim($conn->Overview->Arrival->BasicStop->Arr->Platform->Text);
		    $connection[$i]->arrival->station = $tostation;
//Delay and platform changes //TODO: get Delay from railtime instead - much better information
		    $delay0 = 0;
		    $delay1 = 0;
		    $platformNormal0 = true;
		    $platformNormal1 = true;
		    if ($conn->RtStateList->RtState["value"] == "HAS_DELAYINFO") {

			 $delay0 = tools::transformTime($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Time, $conn->Overview->Date) - $connection[$i]->departure->time;
			 if ($delay0 < 0) {
			      $delay0 = 0;
			 }
//echo "delay: " .$conn->Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Time . "\n";
			 $delay1 = tools::transformTime($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Time, $conn->Overview->Date) - $connection[$i]->arrival->time;
			 if ($delay1 < 0) {
			      $delay1 = 0;
			 }
			 if (isset($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Platform->Text)) {
			      $platform0 = trim($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Platform->Text);
			      $platformNormal0 = false;
			 }
			 if (isset($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text)) {
			      $platform1 = trim($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text);
			      $platformNormal1 = false;
			 }
		    }
		    $connection[$i]->departure->delay = $delay0;
		    $connection[$i]->departure->platform->normal = $platformNormal0;
		    $connection[$i]->arrival->delay= $delay1;
		    $connection[$i]->arrival->platform->normal = $platformNormal1;

		    $trains = array();
		    $vias = array();
		    $directions = array();
		    $j = 0;
		    $k = 0;
		    $connectionindex = 0;
//yay for spaghetti code.
		    if (isset($conn->ConSectionList->ConSection)) {
			 foreach ($conn->ConSectionList->ConSection as $connsection) {

			      if (isset($connsection->Journey->JourneyAttributeList->JourneyAttribute)) {
				   foreach ($connsection->Journey->JourneyAttributeList->JourneyAttribute as $att) {
					if ($att->Attribute["type"] == "NAME") {
					     $trains[$j] = str_replace(" ", "", $att->Attribute->AttributeVariant->Text);
					     $j++;
					}else if($att->Attribute["type"] == "DIRECTION"){
                                            $__stat = new Station();
                                            if($fast == "true"){
                                                $__stat->name = trim($att->Attribute->AttributeVariant->Text);
                                            }else{
                                                $__stat = stations::getStationFromName(trim($att->Attribute->AttributeVariant->Text), $lang);
                                            }
                                            $directions[$k] = $__stat;
					     $k++;
					}
				   }

				   if ($conn->Overview->Transfers > 0 && strcmp($connsection->Arrival->BasicStop->Station['name'], $conn->Overview->Arrival->BasicStop->Station['name']) != 0) {
//current index for the train: j-1
					$departDelay = 0; //Todo: NYImplemented
					$connarray = $conn->ConSectionList->ConSection;
					$departTime = tools::transformTime($connarray[$connectionindex + 1]->Departure->BasicStop->Dep->Time, $conn->Overview->Date);
					$departPlatform = trim($connarray[$connectionindex + 1]->Departure->BasicStop->Dep->Platform->Text);
					$arrivalTime = tools::transformTime($connsection->Arrival->BasicStop->Arr->Time, $conn->Overview->Date);
					$arrivalPlatform = trim($connsection->Arrival->BasicStop->Arr->Platform->Text);
					$arrivalDelay = 0; //Todo: NYImplemented

					$vias[$connectionindex] = new Via();
					$vias[$connectionindex]->arrival = new ViaDepartureArrival();
					$vias[$connectionindex]->arrival->time = $arrivalTime;
					$vias[$connectionindex]->arrival->platform = new Platform();
					$vias[$connectionindex]->arrival->platform->name = $arrivalPlatform;
					$vias[$connectionindex]->arrival->platform->normal = 1;
					$vias[$connectionindex]->departure = new ViaDepartureArrival();
					$vias[$connectionindex]->departure->time = $departTime;
					$vias[$connectionindex]->departure->platform = new Platform();
					$vias[$connectionindex]->departure->platform->name = $departPlatform;
					$vias[$connectionindex]->departure->platform->normal = 1;
					$vias[$connectionindex]->timeBetween = $departTime - $arrivalTime;
	if(isset($directions[$k-1])){
				$vias[$connectionindex]->direction = $directions[$k-1];
 } else {
$vias[$connectionindex]->direction = "unknown";
}
					$vias[$connectionindex]->vehicle = "BE.NMBS." . $trains[$j - 1];
					$vias[$connectionindex]->station = connections::getStationFromHafasLocation($connsection->Arrival->BasicStop->Station['x'],$connsection->Arrival->BasicStop->Station['y'], $lang);
					$connectionindex++;
				   }
			      }
			 }
			 if($connectionindex != 0){
			      $connection[$i]->via = $vias;
			 }
			 
		    }
		    $connection[$i]->departure->vehicle = "BE.NMBS." . $trains[0];
if(isset($directions[0])){
		    $connection[$i]->departure->direction = $directions[0];
}else{
        $connection[$i]->departure->direction = "unknown";
}

		    $connection[$i]->arrival->vehicle = "BE.NMBS." . $trains[sizeof($trains) - 1];
	if(isset($directions[sizeof($directions)-1])){
		    $connection[$i]->arrival->direction = $directions[sizeof($directions)-1];
}else{
	$connection[$i]->arrival->direction = "unknown";
}
		    $i++;
	       }
	  } else {
	       throw new Exception("We're sorry, we could not retrieve the correct data from our sources",2);
	  }
	  return $connection;
     }
     
     private static function getStationFromHafasLocation($locationX,$locationY, $lang){
	  preg_match("/(.)(.*)/",$locationX, $m);
	  $locationX= $m[1] . ".". $m[2];
	  preg_match("/(..)(.*)/",$locationY, $m);
	  $locationY = $m[1] . ".". $m[2];
	  return stations::getStationFromLocation($locationX,$locationY,$lang);
     }
     
}


?>
