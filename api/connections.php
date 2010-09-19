<?php
/*  Copyright 2010 Project iRail
	
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
	
    Source available at http://github.com/Tuinslak/iRail
*/


/**
 * This is the API request handler
 */

header('Content-Type: text/xml');

/*
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
echo "<?xml-stylesheet type=\"text/xsl\" href=\"xmlstylesheets/trains.xsl\" ?>";
*/

include("DataStructs/ConnectionRequest.php");
include("InputHandlers/BRailConnectionInput.php");
include("OutputHandlers/XMLConnectionOutput.php");


$date = "";
$time = "";
$results = "";
$lang = "";
$timeSel = "";
$typeOfTransport = "";
//required vars, output error messages if empty
extract($_GET);
//$from = $_GET["from"];
//$to = $_GET["to"];
//
////optional vars
//$date = $_GET["date"];
//$time = $_GET["time"];
//$results = $_GET["results"];
//$lang = $_GET["lang"];
//$timeSel = $_GET["timesel"];
//$typeOfTransport = $_GET["typeOfTransport"];



if($lang == "") {
    $lang = "EN";
}

if($timeSel == "") {
    $timeSel = "depart";
}

if($results == "" || $results > 6 || $results < 1) {
    $results = 6;
}

if($date == "") {
    $date = "20" . date("ymd");
}

if($time == "") {
    $time = date("H:i");
}

if($typeOfTransport == ""){
    $typeOfTransport = "train";
}
try{
    $request = new ConnectionRequest($from, $to, $time, $date, $timeSel, $results, $lang, $typeOfTransport);
    $input0 = new BRailConnectionInput();
    $output = new XMLConnectionOutput($input0 -> execute($request));
    $output -> printAll();
}catch(Exception $e){
    echo $e->getMessage(); //error handling..
}

// Yeri
// logging includes 
include("../includes/apiLog.php");

// Log request to database
writeLog($_SERVER['HTTP_USER_AGENT'], $from, $to);

?>