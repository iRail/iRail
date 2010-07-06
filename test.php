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
	
	
$ch = curl_init("http://hari.b-rail.be/HAFAS/bin/query.exe/nn?seqnr=1&ident=k4.0538412.1278427697&OK");
curl_setopt($ch, CURLOPT_POST      ,1);
curl_setopt($ch, CURLOPT_POSTFIELDS    , "");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
$Rec_Data = curl_exec($ch);
 
  ob_start();
 header("Content-Type: text/html");
 $Temp_Output = ltrim(rtrim(trim(strip_tags(trim(preg_replace ( "/\s\s+/" , " " , html_entity_decode($Rec_Data)))),"\n\t\r\h\v\0 ")), "%20");
 $Temp_Output = ereg_replace (' +', ' ', trim($Temp_Output));
 $Temp_Output = ereg_replace("[\r\t\n]","",$Temp_Output);
 $Temp_Output = substr($Temp_Output,307,200);
 echo $Temp_Output;
 $Final_Out=ob_get_clean();
 echo $Final_Out;
 curl_close($ch);
} 
 
	
	
	echo $url;
	echo $data;
	echo "<br /><br /><br /><br />";
	echo $body;





?>