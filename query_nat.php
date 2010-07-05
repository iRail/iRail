<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
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
            $txt_warn = "Achtung: Befragen Sie ein offizielles Netz f&#252;r Baustelleninfos.";
            $txt_late = "Achtung: Zug wird verz&#246;gert.";
            $txt_alt = "Achtung: eine alternative Route ist verf&#252;gbar.";
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

$post = http_post_data($url, $data, $request_options); //or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");

// Debug - HTTP POST result
//echo $post . "<br />";
//echo $url . "<br />";
//echo $data . "<br />";

$body = http_parse_message($post)->body; 

// check if nmbs planner is down
if(strstr($body, "[Serverconnection]") && strstr($body, "[Server]")) {
	$down = 1;
}else{
	$down = 0;
}

$body = strstr($body, "<!-- infotravaux-->");

if($body == "" && $down == 0) {
	header('Location: noresults');
}else{

$body = str_replace("<img ", "<img border=\"0\" ", $body);
$body = str_replace("<td ", "<td NOWRAP ", $body);
$body = str_replace("/hafas/img/hafas/", "/hafas/", $body);
//$body = str_replace("/hafas/", "./hafas/", $body);
$body = str_replace("type=\"checkbox\"", "type=\"HIDDEN\"", $body);
// cut off the junk we don't want 
$tmp_body = explode("<td NOWRAP colspan=\"12\">", $body);
$body = $tmp_body[0];
/*
// old site
$tmp_body = explode("<ul class=\"hafasButtons\" title=\"Further options\">",$body);
$body = $tmp_body[0];
$tmp_body = explode("<ul class=\"hafasButtons\" title=\"\">",$body);
$body = $tmp_body[0];
$tmp_body = explode("<ul class=\"hafasButtons\" title=\"Weitere Funktionen\">",$body);
$body = $tmp_body[0];
*/
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

}

echo "<tr><td colspan=\"9\"><form name=\"return\" method=\"post\" action=\"..\">";
echo "<div style=\"font-weight: bold;text-align:center;\"><br /><input type=\"submit\" name=\"submit\" value=\"Back\"></div></td></tr></table>";

echo "<br /></body></html>";
?>