<?php
/*
    Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
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

/*
 * READ THIS:
 *
 * This file contains the most dirty code I've every written.
 * This is a demo file and just contains how we should get railway information.
 * In september this file will not be needed anymore
 *
 * Yours sincerely,
 * Pieter Colpaert
*/

//set content type in the header to XML
header('Content-Type: text/xml');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
echo "<?xml-stylesheet type=\"text/xsl\" href=\"xmlstylesheets/trains.xsl\" ?>";
// National api query
include "../includes/getUA.php"; //→useragent


$url="http://hari.b-rail.be/Hafas/bin/extxml.exe";
//required vars, output error messages if empty
$from = $_GET["from"];
$to = $_GET["to"];

//optional vars
$date = $_GET["date"];
$time = $_GET["time"];
$results = $_GET["results"];
$lang = $_GET["lang"];
$timesel = $_GET["timesel"];
$trainsonly = $_GET["trainsonly"];

if($lang == "") {
    $lang = "EN";
}

if($trainsonly != "0" && $trainsonly != "1") {
    $trainsonly = "0";
}
if($trainsonly == "0") {
    $trainsonly = "1111111111111111";
}else if($trainsonly == "1") {
    $trainsonly = "0111111000000000";
}

if($timesel == "") {
    $timesel = "depart";
}

if($results == "" || $results > 6 || $results < 1) {
    $results = 6;
}

if($date == "") {
    $date = date("dmy");
}

//reform date to wanted structure
preg_match("/(..)(..)(..)/si",$date, $m);
$date = "20" . $m[3] . $m[2] . $m[1];

if($time == "") {
    $time = date("Hi");
}

//reform time to wanted structure
preg_match("/(..)(..)/si",$time, $m);
$time = $m[1] . ":" . $m[2];

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

//first we're going to try to get the right internal ID's for the stations
$postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="irail" lang="EN">
<LocValReq id="from" maxNr="1">
<ReqLoc match="'. $from.'" type="ST"/>
</LocValReq>
<LocValReq id="to" maxNr="1">
<ReqLoc match="'. $to.'" type="ST"/>
</LocValReq>
</ReqC>';

$post = http_post_data($url, $postdata, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
$idbody = http_parse_message($post)->body;
//get id's of the stations out of it to use in the real request
preg_match_all("/externalId=\"(.*?)\"/si", $idbody,$matches);
$idfrom = $matches[1][0];
$idto = $matches[1][1];
//Get real from and to from this
preg_match_all("/id=\"...?.?\"><Station name=\"(.*?)\"/si", $idbody,$matches);
$from = $matches[1][0];
$to = $matches[1][1];
//Now let's use these Id's to get the information we need
$postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="irail" lang="'. $lang .'">
<ConReq>
<Start min="0">
<Station externalId="'. $idfrom .'" distance="0">
</Station>
<Prod prod="'. $trainsonly .'">
</Prod>
</Start>
<Dest min="0">
<Station externalId="'. $idto .'" distance="0">
</Station>
</Dest>
<Via>
</Via>
<ReqT time="'. $time .'" date="'. $date .'" a="0">
</ReqT>
<RFlags b="0" f="'. $results .'">
</RFlags>
<GISParameters>
<Front>
</Front>
<Back>
</Back>
</GISParameters>
</ConReq>
</ReqC>';


$post = http_post_data($url, $postdata, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
$body = http_parse_message($post)->body;
//DBG: echo $body;

//output

// Find connections
$connectionnumber = 0;

preg_match_all("/<Connection .*?>(.*?)<\/Connection>/si", $body, $matches);
$connections = $matches[1];
echo "<connections>";
foreach($connections as $i => $value) {
    preg_match("/<Overview><Date>(.{8})<\/Date>/si", $value, $m);
    $date = $m[1];
    preg_match("/..(..)(..)(..)/si",$date, $m);
    $date = $m[3] . $m[2] . $m[1];
    preg_match("/<Dep getIn=\"YES\">\s*<Time>00d(..:..):00<\/Time>/si", $value, $m);
    $time_dep = $m[1];
    preg_match("/<Arr getOut=\"YES\">\s*<Time>00d(..:..):00<\/Time>/si", $value, $m);
    $time_arr = $m[1];

    //needs fixing: in some cases the train is not 7 chars
    preg_match_all("/<Attribute type=\"NAME\"><AttributeVariant type=\"NORMAL\"><Text>(.*?)<\/Text>/si", $value, $trains);

    preg_match("/<Duration><Time>00d0(.:..):00<\/Time>/is", $value, $matches);
    $duration = $matches[1];
    echo "<connection id=\"" . $i . "\">";
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
    echo preg_match("/HAS_DELAYINFO/si", $value);
    echo "</delay>";

    echo "<trains>";
    foreach($trains[1] as $i => $train) {
        echo "<train>". $train ."</train>";
    }
    echo "</trains>";

    echo "</connection>";

}

echo "</connections>";

// Yeri
// logging includes 
include("../includes/apiLog.php");

// Log request to database
writeLog($_SERVER['HTTP_USER_AGENT'], $from, $to, "none (trains.php)", $_SERVER['REMOTE_ADDR']);
?>