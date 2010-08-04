<?php
/*
    Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
    Copyright 2010 Pieter Colpaert (pieterc aŧ member.fsf.org - http://bonsansnom.wordpress.com)

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

	http://blog.irail.be - http://irail.be
	
	source available at http://github.com/Tuinslak/iRail
 */

// National api query
include "../includes/getUA.php"; //→useragent

//get the GET vars

//required vars, output error messages if empty
$from = $_GET["from"];
$to = $_GET["to"];

//optional vars
$date = $_GET["date"];
$time = $_GET["time"];
$results = $_GET["results"];
$lang = $_GET["lang"];

if($lang == "") {
    $lang = "EN";
}

if($results == "") {
    $results = 1;
}

if($date == "") {
    $date = date("dmy");
}

if($time == "") {
    $time = date("Hi");
}

// if bad stations, redirect
if($from == "" || $to == "" || $from == $to) {
    header('Location: ..');
}


// prepare HTTP request
$request_options = array(
        referer => "http://irail.be/",
        timeout => "30",
        useragent => $irailAgent,
);


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
//$time = $h . $m;
//$date = $d . $mo . $y;
// Create google map vars without [B] stuff (edit: new nmbs site doesn't use [B] anymore!)
$m_from = $from;
$m_to = $to;

// Correct Brussels South/Midi to use "-" instead of space; else = error
if(strtoupper($from) == "BRUSSEL MIDI") {
    $from = "BRUSSEL-MIDI";
}
if(strtoupper($from) == "BRUSSEL ZUID") {
    $from = "BRUSSEL-ZUID";
}

$data = "&REQ0JourneyStopsS0A=1&fromTypeStation=select&REQ0JourneyStopsS0F=selectStationAttribute;GA&REQ0JourneyStopsS0G=";
$data .= $from;
$data .= "&REQ0JourneyStopsZ0A=1&toTypeStation=select&REQ0JourneyStopsZ0F=selectStationAttribute;GA&REQ0JourneyStopsZ0G=";
$data .= $to;
$data .= "&date=" . $date;
$data .= "&time=" . $time;
$data .= "&timesel=" . "depart";
$data .= "&";
$data .= "start=submit";

$post = http_post_data($url, $data, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");

// Debug - HTTP POST result
//echo $post . "<br />";
//echo $url . "<br />";
//echo $data . "<br />";

$body = http_parse_message($post)->body;

// check if nmbs planner is down
if(strstr($body, "[Serverconnection]") && strstr($body, "[Server]")) {
    $down = 1;
}else {
    $down = 0;
}

// TEST Stations !!

// tmp body in case of special stationnames (http://yeri.be/cc)
$tmp_body = $body;

$body = strstr($body, "<!-- infotravaux-->");

if($body == "" && $down == 0) {
    header('Location: noresults');//TODO: put error instead
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

// Find connections
$connectionnumber = 0;

$connections = preg_split("/infotravaux/", $body);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
echo "<connections>";
foreach($connections as $i => $value) {
    if($i == 0){
        continue;
    }
    //times: <td NOWRAP class="sepline">23:22<br />23:36</td>
    //duration: <td NOWRAP headers="hafasOVDuration" class="sepline nowrap center borderright">
    //0:14
    //</td>
    //
    $matches = array();
    //DBG: echo $value;
    //$doll is a nonused var
    $doll = preg_match("/.*(\d\d:\d\d).{6}(\d\d:\d\d).*/is", $value, $matches);
    $time_dep = $matches[1];
    $time_arr = $matches[2];
    $doll = preg_match("/\s(\d:\d\d)/is", $value, $matches);
    $duration = $matches[1];
    echo "<connection>";
    echo "<departure>";
    echo "<station>";
    echo $from;
    echo "</station>";
    echo "<time>";
    echo $time_dep;
    echo "</time>";
    echo "<date>";
    echo $date;
    echo "</date>";
    echo "</departure>";

    echo "<arrival>";
    echo "<station>";
    echo $to;
    echo "</station>";
    echo "<time>";
    echo $time_arr;
    echo "</time>";
    echo "<date>";
    echo $date;
    echo "</date>";
    echo "</arrival>";

    echo "<duration>";
    echo $duration;
    echo "</duration>";

    echo "<delay>";
    echo $late;
    echo "</delay>";

    echo "</connection>";

}
echo "</connections>";
?>
