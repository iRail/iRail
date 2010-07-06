<?php

$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";

if(true) {
	$data = "";
	$data = "&REQ0JourneyStopsS0A=1&fromTypeStation=select&REQ0JourneyStopsS0F=selectStationAttribute;GA&REQ0JourneyStopsS0G=";
	$data .= $_POST["from"];
	$data .= "&REQ0JourneyStopsZ0A=1&toTypeStation=select&REQ0JourneyStopsZ0F=selectStationAttribute;GA&REQ0JourneyStopsZ0G=";
	$data .= $_POST["to"];
	$data .= "&date=" . $date;
	$data .= "&time=" . $time;
	$data .= "&timesel=" . $_POST["timesel"];
	$data .= "&";
	$data .= "start=bevestig";
	
	$post = http_post_data($url, $data, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
	
	$body = http_parse_message($post)->body;
	
	echo $url;
	echo $data;
	echo "<br /><br /><br /><br />";
	echo $body;
}

?>