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

// international query page

// includesiRail
include 'includes/getUA.php';

extract($_POST);
// check on wrong stations
if($from == "" || $to == "" || $from == $to) {
	header('Location: ..');
}

// set stations in cookie
setcookie("intfrom", $_POST['from'], time()+60*60*24*360);
setcookie("intto", $_POST['to'], time()+60*60*24*360);

// set request options
$request_options = array(
			referer => "http://irail.be/", 
			timeout => "30",
			useragent => $irailAgent, 
		);
			
// get lang from cookie
$lang = $_COOKIE["language"];

// set text
switch($lang) {
case "EN": 	$url = "http://plannerint.b-rail.be/bin/query.exe/en?L=b-rail";
			$txt_warn = "Warning: additional information available on the official website.";
			break;
case "NL":	$url = "http://plannerint.b-rail.be/bin/query.exe/nn?L=b-rail";
			$txt_warn = "Opgelet: er is belangrijke werfinfo op de offici&#235;le website.";
			break;
case "FR":  $url = "http://plannerint.b-rail.be/bin/query.exe/f?L=b-rail";
            $txt_warn = "Attention: consultez le site web officiel pour des infos chantier importante.";
			break;
case "DE":  $url = "http://plannerint.b-rail.be/bin/query.exe/d?L=b-rail";
            $txt_warn = "Achtung: Befragen Sie ein offizielles Netz f&#252;r Baustelleninfos.";
			break;
default:	$url = "http://plannerint.b-rail.be/bin/query.exe/en?L=b-rail";
			$txt_warn = "Warning: additional information available on the official website.";
			break;
}

// Debug - variable content
//echo $from;
//echo $to;
//echo $d;
//echo $mo;
//echo $y;
//echo $h;
//echo $m;

$time = $h . $m;
$date = $d . $mo . $y;
$m_from = $_POST["from"];
$m_to = $_POST["to"];

$data = "from=" . $_POST["from"] . " "; 
$data .= "&to=" . $_POST["to"] . " ";
$data .= "&date=" . $date;
$data .= "&time=" . $time;
$data .= "&timesel=" . $_POST["timesel"];
$data .= "&";
$data .= "start=submit";

$post = http_post_data($url, $data, $request_options) or die("NMBS/SNCB website timeout. Please refresh.");

// Debug - HTTP POST result
//echo $irailAgent . "<br>";
//echo $post . "<br>";
//echo $url . "<br>";
//echo $data . "<br>";
//echo $request_options . "<br>";

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

$body = strstr($body, '<table CELLSPACING="0" CELLPADDING="0" BORDER="0" WIDTH="100%" BGCOLOR="#FFFFFF">');

if($body == "" && $down == 0) {
	header('Location: noresults');
}else{

$body = str_replace('<table CELLSPACING="0" CELLPADDING="0" BORDER="0" WIDTH="100%" BGCOLOR="#FFFFFF">', '<table CELLSPACING="0" CELLPADDING="0" BORDER="0" BGCOLOR="#FFFFFF">', $body);
$body = str_replace("<img ", "<img border=\"0\" ", $body);
$body = str_replace("<td ", "<td NOWRAP ", $body);
$body = str_replace("type=\"checkbox\"", "type=\"HIDDEN\"", $body);
$body = str_replace('<th class="resultdark" width="10">&nbsp;Details&nbsp;</th>', "<td><!-- iRail.be / details --></td>", $body);
$body = str_replace('<th class="resultdark" width="10">&nbsp;D&#233;tails&nbsp;</th>', "<td><!-- iRail.be / details --></td>", $body);
$body = str_replace('<th ', '<td style="text-align:center;" ', $body);
$body = str_replace('<TD BGCOLOR="#999999" colspan="18"><IMG SRC="/img/space.gif" WIDTH="1" HEIGHT="1" BORDER="0"></TD>', '<!-- hidden td -->', $body);
//$body = str_replace('BORDER="0"', 'border="1"', $body);
$body = str_replace('</th>', '</td>', $body);
$body = str_replace('<td class="resultdark"><img src="/img/th_separator.gif" width="1" height="19" border="0"></td>', "<!-- space -->", $body);
$body = str_replace('<td class="resultdark" colspan="5">', '<td class="resultdark" colspan="7">', $body);

$tmp_body = explode('<td NOWRAP class="resultdark"><input type="image" name=',$body);
$body = $tmp_body[0];

// Find if there's a warning icon
if(strstr($body, "/icon_warning.gif")) {
	$warning = 1;
}

if($down == 1) {
        $body = "NMBS/SNCB site currently unavailable. Please retry in a few minutes.";
}


$header = '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>iRail - Results</title>
  <meta http-equiv="Cache-control" content="no-cache">
  <link href="/css/query.css" rel="stylesheet" >
  <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
  <script>
  	addEventListener(\'load\', function() { setTimeout(hideAddressBar, 0); }, false);
  	function hideAddressBar() { window.scrollTo(0, 1); }
  </script>
</head>
<body>
';

$footer = "</table>";

echo $header;
echo $body;
echo $footer;

if($warning == 1) {
	echo "<p style=\"margin:20px;\"><img src=\"./hafas/img/icon_warning.gif\" alt=\"Warning icon\"> $txt_warn </p>";
}
}

echo "<form name=\"return\" method=\"post\" action=\"international\">";
echo "<div style=\"font-weight: bold;text-align:center;\"><br><input type=\"submit\" name=\"submit\" value=\"Back\"></div>";

echo "<br></body></html>";
?>
