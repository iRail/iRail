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

include("DataStructs/ConnectionRequest.php");
include("InputHandlers/BRailConnectionInput.php");
include("OutputHandlers/XMLConnectionOutput.php");

include("../includes/apiLog.php");


$date = "";
$time = "";
$results = "";
$lang = "";
$timeSel = "";
$typeOfTransport = "";

//required vars, output error messages if empty
extract($_GET);

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
    $date = date("dmy");
}

//TODO: move this to constructor of ConnectionRequest

//reform date to needed train structure
preg_match("/(..)(..)(..)/si",$date, $m);
$date = "20" . $m[3] . $m[2] . $m[1];

if($time == "") {
    $time = date("Hi");
}

//reform time to wanted structure
preg_match("/(..)(..)/si",$time, $m);
$time = $m[1] . ":" . $m[2];

if($typeOfTransport == "") {
    $typeOfTransport = "train";
}

try {
    $request = new ConnectionRequest($from, $to, $time, $date, $timeSel, $results, $lang, $typeOfTransport);
    $input0 = new BRailConnectionInput();
    $connections = $input0 -> execute($request);
    $output = new XMLConnectionOutput($connections);
    $output -> printAll();
    // Log request to database
    writeLog($_SERVER['HTTP_USER_AGENT'], $connections[0] -> getDepart() -> getStation() -> getName(), $connections[0] -> getArrival() -> getStation() -> getName(), "none (connections.php)", $_SERVER['REMOTE_ADDR']);

}catch(Exception $e) {
    writeLog($_SERVER['HTTP_USER_AGENT'],"", "", "Error in connections.php: " . $e, $_SERVER['REMOTE_ADDR']);
    echo $e->getMessage(); //error handling..
}


?>