<?php
/* 	Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
    Copyright 2010 Pieter Colpaert (pieter@irail.be - http://bonsansnom.wordpress.com)

	This file is part of iRail.

    iRail is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    iRail is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with iRail.  If not, see <http://www.gnu.org/licenses/>.

	http://project.irail.be - http://irail.be
	
	source available at http://github.com/Tuinslak/iRail
*/
 
// National query page

// includes
include 'includes/getUA.php';

// if bad stations, return
if($from == "" || $to == "" || $from == $to) {
	header('Location: ..');
}

// save stations in cookies
setcookie("from", $_POST['from'], time()+60*60*24*360);
setcookie("to", $_POST['to'], time()+60*60*24*360);

// prepare HTTP request
$request_options = array(
			referer => "http://irail.be/", 
			timeout => "30",
			useragent => $irailAgent, 
		);

// get lang from cookie
$lang = $_COOKIE["language"];

// set text
switch($lang) {
case "EN": 	$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";
			$txt_warn = "Warning: additional information available on the official website.";
			$txt_late = "Warning: train is delayed.";
			$txt_alt = "Warning: alternative route available.";
			break;
case "NL":	$url = "http://hari.b-holding.be/hafas/bin/query.exe/nn?";
			$txt_warn = "Opgelet: er is belangrijke werfinfo op de offici&#235;le website.";
			$txt_late = "Opgelet: trein heeft vertraging.";
			$txt_alt = "Opgelet: alternatieve route beschikbaar.";
			break;
case "FR":  $url = "http://hari.b-holding.be/hafas/bin/query.exe/f?";
            $txt_warn = "Attention: consultez le site web officiel pour des infos chantier importante.";
            $txt_late = "Attention: train a du retard.";
            $txt_alt = "Attention: itin&#233;raire alternatif est disponible.";
			break;
case "DE":  $url = "http://hari.b-holding.be/hafas/bin/query.exe/d?";
            $txt_warn = "Achtung! Es gibt wichtige Informationen vor Ort auf der offiziellen Webseite!";
            $txt_late = "Achtung! Zug verz&#246;gert sich!";
            $txt_alt = "Achtung! eine alternatieve Route ist verf&#252;gbar!";
			break;
default:	$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";
			$txt_warn = "Warning: additional information available on the official website.";
			$txt_late = "Warning: train is delayed.";
			$txt_alt = "Warning: alternative route available.";
			break;
}

// Debug - variable content
/*
echo $from . "<br />";
echo $to . "<br />";
echo "d: " . $d . "<br />";
echo "mo: " . $mo . "<br />";
echo "y: " . $y . "<br />";
echo "h: " . $h . "<br />";
echo "m: " . $m . "<br />";
*/

// Create time vars
$time = $h . $m;
$date = $d . $mo . $y;
// Create google map vars without [B] stuff (edit: new nmbs site doesn't use [B] anymore!)
$m_from = $_POST["from"];
$m_to = $_POST["to"];

// Correct Brussels South/Midi to use "-" instead of space; else = error
if(strtoupper($_POST["from"]) == "BRUSSEL MIDI") {
	$_POST["from"] = "BRUSSEL-MIDI";
}
if(strtoupper($_POST["from"]) == "BRUSSEL ZUID") {
	$_POST["from"] = "BRUSSEL-ZUID";
}

$data = "&REQ0JourneyStopsS0A=1&fromTypeStation=select&REQ0JourneyStopsS0F=selectStationAttribute;GA&REQ0JourneyStopsS0G=";
$data .= $_POST["from"];
$data .= "&REQ0JourneyStopsZ0A=1&toTypeStation=select&REQ0JourneyStopsZ0F=selectStationAttribute;GA&REQ0JourneyStopsZ0G=";
$data .= $_POST["to"];
$data .= "&date=" . $date;
$data .= "&time=" . $time;
$data .= "&timesel=" . $_POST["timesel"];
$data .= "&";
$data .= "start=submit";

$post = http_post_data($url, $data, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");

// Debug - HTTP POST result
//echo $post . "<br />";
//echo $url . "<br />";
//echo $data . "<br />";

$body = http_parse_message($post)->body; 

//This code fixes most hated issue #2 →→ You can buy me a beer in Ghent at anytime if you leave me a message at +32484155429
$dummy = preg_match("/(query\.exe\/..\?seqnr=1&ident=.*?).OK.focus\" id=\"formular\"/si", $body, $matches);
if($matches[1] != ""){
    //DEBUG:echo $matches[1];
    //scrape the date & time layout from $body
    preg_match("/value=\"(.., ..\/..\/..)\" onblur=\"checkWeekday/si", $body, $datelay);
    $datelay[1]= urlencode($datelay[1]);
    preg_match("/name=\"REQ0JourneyTime\" value=\"(..:..)\"/si", $body, $timelay);
    $timelay[1] = urlencode($timelay[1]);
    $passthrough_url = "http://hari.b-rail.be/HAFAS/bin/".$matches[1] . "&queryPageDisplayed=yes&REQ0JourneyStopsS0A=1%26fromTypeStation%3Dhidden&REQ0JourneyStopsS0K=S-0N1&REQ0JourneyStopsZ0A=1%26toTypeStation%3Dhidden&REQ0JourneyStopsZ0K=S-1N1&REQ0JourneyDate=". $datelay[1] ."&wDayExt0=Ma|Di|Wo|Do|Vr|Za|Zo&REQ0JourneyTime=". $timelay[1] ."&REQ0HafasSearchForw=1&REQ0JourneyProduct_prod_list=". $trainsonly ."&start=Submit";
    //DEBUG:echo "\n". $passthrough_url;
    $post = http_post_data($passthrough_url, null, $request_options);
    $body = http_parse_message($post)->body;
}

// check if nmbs planner is down
if(strstr($body, "[Serverconnection]") && strstr($body, "[Server]")) {
	$down = 1;
}else{
	$down = 0;
}

// TEST Stations !!

// tmp body in case of special stationnames (http://yeri.be/cc)
$tmp_body = $body;

$body = strstr($body, "<!-- infotravaux-->");

if($body == "" && $down == 0) {
	// redirect to no results page
	header('Location: noresults');
	
	/*
	// doesn't work .. :(
	
	$tmp_body = stristr($tmp_body, "http://hari.b-rail.be/HAFAS/bin/query.exe/nn?seqnr=1");
	// requires php 5.3 !! 
	$tmp_url = stristr($tmp_body, "\"", true);
	$tmp_url = str_replace("seqnr=1", "seqnr=2", $tmp_url); 
	echo $tmp_url;
	
	$post = http_post_data($tmp_url, "", $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
	$body = http_parse_message($post)->body;
	echo $body;
	return;
	
	*/
}

$body = str_replace("<img ", "<img border=\"0\" ", $body);
$body = str_replace("<td ", "<td NOWRAP ", $body);
$body = str_replace("/hafas/img/hafas/", "/hafas/", $body);
$body = str_replace("type=\"checkbox\"", "type=\"HIDDEN\"", $body);
// cut off the junk we don't want 
$tmp_body = explode("<td NOWRAP colspan=\"12\">", $body);
$body = $tmp_body[0];
// replace invalid b-rail shizzle
$body = str_replace("http://hari.b-rail.be/HAFAS/bin/query.exe", "http://maps.google.be/?saddr=Station $m_from&daddr=Station $m_to\" target='_blank' id=\"",$body);
$body = str_replace("http://hari.b-rail.be/hafas/bin/query.exe", "http://maps.google.be/?saddr=Station $m_from&daddr=Station $m_to\" target='_blank' id=\"",$body);  
$body = str_replace('<a href="http://hari.b-rail.be/HAFAS/bin/stboard.exe', '<a target="_blank" href="http://hari.b-rail.be/HAFAS/bin/stboard.exe', $body);
$body = str_replace('<a href="http://hari.b-rail.be/hafas/bin/stboard.exe', '<a target="_blank" href="http://hari.b-rail.be/hafas/bin/stboard.exe', $body);

// Find if there's a warning icon
if(strstr($body, "/icon_warning.gif")) {
	$warning = 1;
}

// Find if trains are late... AGAIN !!!!
if(strstr($body, "/rt_late_normal_overview.gif") || strstr($body, "/rt_late_critical_overview_2.gif")) {
	$late = 1;
}

// Find if an alternative route is available (due to lateness...)
if(strstr($body, "/rt_late_alternative_overview.gif")) {
	$alt_route = 1;
}

// output error if nmbs site is down down down and down !
if($down == 1) {
	$body = "<br />NMBS/SNCB site currently unavailable. Please retry in a few minutes.";
}


$header = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html lang="en">
			<head>
			<title>iRail - Results</title>
			<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
			<link href="css/query.css" rel="stylesheet" type="text/css" />
			<link rel="apple-touch-icon" href="./img/irail.png" />
			<link rel="shortcut icon" type="image/x-icon" href="./img/favicon.ico">
			<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
			<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
			<script type="application/x-javascript">
			addEventListener(\'load\', function() { setTimeout(hideAddressBar, 0); }, false);
			function hideAddressBar() { window.scrollTo(0, 1); }
			</script>
		   </head><body>';

if($down != 1) {
	$header .= '
		<div class="anchorTop">
		<table align="left" cellpadding="0" cellspacing="1" bgcolor="FFFFFF" summary="Train Info">
		<tr>
		<td></td>
		<th>Station </th>
		<th>Date </th>
		<td></td>
		<th>Time </th>
		<td></td>
		<th>Duration </th>
		<th>Changes </th>
		<th>Transportation</th>
		</tr>'; 
	}

echo $header;
echo $body;

if($warning == 1 || $late == 1 || $alt_route == 1) {

	echo "<tr><td colspan=\"9\"><div style=\"margin:20px;font-size:11px;\">";

	if($warning == 1) {
		echo "<img src=\"./HAFAS/img/icon_warning.gif\" alt=\"Warning icon\" /> $txt_warn <br />";
	}
	if($late == 1) {
		echo "<img src=\"./HAFAS/img/rt_late_normal_overview.gif\" alt=\"Late icon\" /> $txt_late <br />";
	}
	if($alt_route == 1) {
		echo "<img src=\"./HAFAS/img/rt_late_alternative_overview.gif\" alt=\"Alternative route icon\" /> $txt_alt <br />";
	}

echo "</div></td></tr>";

}

echo "<tr><td colspan=\"9\"><form name=\"return\" method=\"post\" action=\"..\">";
echo "<div style=\"font-weight: bold;text-align:center;\"><br /><input type=\"submit\" name=\"submit\" value=\"Back\"></div></td></tr></table>";

echo "<br /></body></html>";
?>