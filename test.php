<?php

$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";

if(true) {
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
	
	echo $url;
	echo $data;
	echo "<br /><br /><br /><br />";
	echo $body;
}

?>