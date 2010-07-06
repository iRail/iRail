<?php

$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";


	$data = "";
	$data = "&REQ0JourneyStopsS0A=1&fromTypeStation=select&REQ0JourneyStopsS0F=selectStationAttribute;GA&REQ0JourneyStopsS0G=";
	$data .= "aalst";
	$data .= "&REQ0JourneyStopsZ0A=1&toTypeStation=select&REQ0JourneyStopsZ0F=selectStationAttribute;GA&REQ0JourneyStopsZ0G=";
	$data .= "vilvoorde";
	$data .= "&date=" . $date;
	$data .= "&time=" . $time;
	$data .= "&timesel=" . $_POST["timesel"];
	$data .= "&";
	$data .= "start=bevestig";
	
	$url .= $data;
	
	$data =	"__utma=1.1333636757.1252318191.1254576431.1255378610.7; __utmb=1.4.10.1278422421; __utmz=1.1278422421.7.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmc=1";
	
	$post = http_post_data($url, $data, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
	
	$body = http_parse_message($post)->body;
	
	// http://hari.b-rail.be/HAFAS/bin/query.exe/nn?seqnr=31&ident=k4.0538412.1278427697&OK#focus&REQ0HafasSearchForw=1&REQ0JourneyDate=Di%2C%2006%2F07%2F10&REQ0JourneyDest_Bike_enable=0&REQ0JourneyDest_KissRide_enable=0&REQ0JourneyProduct_prod_list=1%3A0111111000000000&REQ0JourneyStopsS0A=1&REQ0JourneyStopsS0K=S-0N1&REQ0JourneyStopsZ0A=1&REQ0JourneyStopsZ0K=S-1N1&REQ0JourneyTime=15%3A20&queryPageDisplayed=yes&start=Bevestig&wDayExt0=Ma%7CDi%7CWo%7CDo%7CVr%7CZa%7CZo
	
$ch = curl_init("http://hari.b-rail.be/HAFAS/bin/query.exe/nn?seqnr=31&ident=k4.0538412.1278427697&OK#focus&REQ0HafasSearchForw=1&REQ0JourneyDate=Di%2C%2006%2F07%2F10&REQ0JourneyDest_Bike_enable=0&REQ0JourneyDest_KissRide_enable=0&REQ0JourneyProduct_prod_list=1%3A0111111000000000&REQ0JourneyStopsS0A=1&REQ0JourneyStopsS0K=S-0N1&REQ0JourneyStopsZ0A=1&REQ0JourneyStopsZ0K=S-1N1&REQ0JourneyTime=15%3A20&queryPageDisplayed=yes&start=Bevestig&wDayExt0=Ma%7CDi%7CWo%7CDo%7CVr%7CZa%7CZo");
curl_setopt($ch, CURLOPT_POST      ,1);
curl_setopt($ch, CURLOPT_POSTFIELDS    , "");
curl_setopt($ch, CURLOPT_COOKIE, "__utma=1.1333636757.1252318191.1254576431.1255378610.7; __utmz=1.1278422421.7.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmc=1; __utmb=1.1.10.1278427684");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
$Rec_Data = curl_exec($ch);
 
//ob_start();
//header("Content-Type: text/html");
echo $Rec_Data;
$Final_Out=ob_get_clean();
echo "<br /><br /><br /><br /><br /><br /><br /><br />";
echo $Final_Out;
curl_close($ch);
 
 
	
	
/*	echo $url;
	echo $data;
	echo "<br /><br /><br /><br />";
	echo $body;
*/




?>