<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
 */
 
// National query page

if($from == "" || $to == "" || $from == $to) {
	header('Location: ..');
}

setcookie("from", $_POST['from'], time()+60*60*24*360);
setcookie("to", $_POST['to'], time()+60*60*24*360);

$request_options = array(referer => "http://irail.be/", 
			timeout => "30",
			useragent => "iRail by Tuinslak", 
			);
$lang = $_COOKIE["language"];

switch($lang) {
case "EN": 	$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";
			$txt_warn = "Warning: additional information available on the official website.";
			break;
case "NL":	$url = "http://hari.b-holding.be/hafas/bin/query.exe/nn?";
			$txt_warn = "Opgelet: er is belangrijke werfinfo op de offici&#235;le website.";
			break;
case "FR":  $url = "http://hari.b-holding.be/hafas/bin/query.exe/f?";
            $txt_warn = "Attention: consultez le site web officiel pour des infos chantier importante.";
			break;
case "DE":  $url = "http://hari.b-holding.be/hafas/bin/query.exe/d?";
            $txt_warn = "Achtung: Befragen Sie ein offizielles Netz f&#252;r Baustelleninfos.";
			break;
default:	$url = "http://hari.b-holding.be/hafas/bin/query.exe/en?";
			$txt_warn = "Warning: additional information available on the official website.";
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

$time = $h . $m;
$date = $d . $mo . $y;
$m_from = $_POST["from"];
$m_to = $_POST["to"];

$data = "from=" . $_POST["from"] . "[B]";
$date .= "&typefrom=1&fromTypeStation=select&REQ0JourneyStopsS0F=selectStationAttribute;GA";
$data .= "&to=" . $_POST["to"] . "[B]";
$date .= "&typeto=1&toTypeStation=select&REQ0JourneyStopsZ0F=selectStationAttribute;GA";
$data .= "&date=" . $date;
$data .= "&time=" . $time;
$data .= "&timesel=" . $_POST["timesel"];
$data .= "&";
$data .= "start=submit";

$post = http_post_data($url, $data, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");

// Debug - HTTP POST result
//echo $post;

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
$tmp_body = explode("<ul class=\"hafasButtons\" title=\"Further options\">",$body);
$body = $tmp_body[0];
$tmp_body = explode("<table class=\"hafasButtons\" cellspacing=\"0\" summary=\"\">",$body);
$body = $tmp_body[0];
$tmp_body = explode("<table class=\"hafasButtons\" cellspacing=\"0\" summary=\"Weitere Funktionen\">",$body);
$body = $tmp_body[0];
$body = str_replace("http://hari.b-rail.be/HAFAS/bin/query.exe", "http://maps.google.be/?saddr=Station $m_from&daddr=Station $m_to\" target='_blank' id=\"",$body);
$body = str_replace("http://hari.b-rail.be/hafas/bin/query.exe", "http://maps.google.be/?saddr=Station $m_from&daddr=Station $m_to\" target='_blank' id=\"",$body);  
$body = str_replace('<a href="http://hari.b-rail.be/HAFAS/bin/stboard.exe', '<a target="_blank" href="http://hari.b-rail.be/HAFAS/bin/stboard.exe', $body);
$body = str_replace('<a href="http://hari.b-rail.be/hafas/bin/stboard.exe', '<a target="_blank" href="http://hari.b-rail.be/hafas/bin/stboard.exe', $body);


// Find if there's a warning icon
if(strstr($body, "/icon_warning.gif")) {
	$warning = 1;
}

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
<th>Products</th>
</tr>';
}

$footer = "</table>";

echo $header;
echo $body;
echo $footer;

if($warning == 1) {
	echo "<p style=\"margin:20px;\"><img src=\"./hafas/img/icon_warning.gif\" alt=\"Warning icon\" /> $txt_warn </p>";
}
}

echo "<form name=\"return\" method=\"post\" action=\"..\">";
echo "<div style=\"font-weight: bold;text-align:center;\"><br /><input type=\"submit\" name=\"submit\" value=\"Back\"></div>";



include 'ga.inc.php';

echo "<br /></body></html>";
?>
