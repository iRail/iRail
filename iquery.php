<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
 */
 
// international query page

if($from == "" || $to == "" || $from == $to) {
	header('Location: ..');
}

setcookie("intfrom", $_POST['from'], time()+60*60*24*360);
setcookie("intto", $_POST['to'], time()+60*60*24*360);

$request_options = array(referer => "http://irail.be/", 
			 useragent => "iRail by Tuinslak", 
			 timeout => "30",
			);
$lang = $_COOKIE["language"];

switch($lang) {
case "EN": 	$url = "http://plannerint.b-rail.be/bin/query.exe/en?L=b-rail";
		$txt_warn = "Warning: additional information available on the official website.";
		break;
case "NL":	$url = "http://plannerint.b-rail.be/bin/query.exe/nn?L=b-rail";
		$txt_warn = "Opgelet: er is belangrijke werfinfo op de offici&#235;le website.";
		break;
case "FR":      $url = "http://plannerint.b-rail.be/bin/query.exe/f?L=b-rail";
                $txt_warn = "Attention: consultez le site web officiel pour des infos chantier importante.";
		break;
case "DE":      $url = "http://plannerint.b-rail.be/bin/query.exe/d?L=b-rail";
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
//echo $post;

$body = http_parse_message($post)->body; 

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


$header = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
<title>iRail :: results</title>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<link href="query.css" rel="stylesheet" type="text/css" />
<link rel="apple-touch-icon" href="./img/irail.png" />
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<script type="application/x-javascript">
	addEventListener(\'load\', function() { setTimeout(hideAddressBar, 0); }, false);
	function hideAddressBar() { window.scrollTo(0, 1); }
</script>
</head><body>
';

$footer = "</table>";

echo $header;
echo $body;
echo $footer;

if($warning == 1) {
	echo "<p style=\"margin:20px;\"><img src=\"./hafas/img/icon_warning.gif\" alt=\"Warning icon\" /> $txt_warn </p>";
}
}

echo "<form name=\"return\" method=\"post\" action=\"international\">";
echo "<div style=\"font-weight: bold;text-align:center;\"><br /><input type=\"submit\" name=\"submit\" value=\"Back\"></div>";



include 'ga.inc.php';

echo "<br /></body></html>";
?>