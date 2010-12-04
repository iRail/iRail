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

//set content type in the header to XML
header('Content-Type: application/xml');

include_once("DataStructs/ConnectionRequest.php");
include_once("InputHandlers/BRailConnectionInput.php");
include_once("OutputHandlers/OldAPIOutput.php");

include_once("../includes/apiLog.php");


$date = "";
$time = "";
$results = "";
$lang = "";
$timesel = "";
$trainsonly = "";

//required vars, output error messages if empty
extract($_GET);

if($lang == "") {
    $lang = "EN";
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

//reform date to needed train structure
preg_match("/(..)(..)(..)/si",$date, $m);
$date = "20" . $m[3] . $m[2] . $m[1];

if($time == "") {
    $time = date("Hi");
}

//reform time to wanted structure
preg_match("/(..)(..)/si",$time, $m);
$time = $m[1] . ":" . $m[2];

if($trainsonly == "") {
    $trainsonly = "train";
}else if($trainsonly == 1) {
    $trainsonly = "train";
}else {
    $trainsonly = "all";
}

try {
    $request = new ConnectionRequest($from, $to, $time, $date, $timesel, $results, $lang, $trainsonly);
    $input = new BRailConnectionInput();
    $connections = $input -> execute($request);
    $output = new OldAPIOutput($connections);
    $output -> printAll();
    
    // Log request to database
    writeLog($_SERVER['HTTP_USER_AGENT'], $connections[0] -> getDepart() -> getStation() -> getName(), $connections[0] -> getArrival() -> getStation() -> getName(), "none (trains.php)", $_SERVER['REMOTE_ADDR']);
}catch(Exception $e) {
    writeLog($_SERVER['HTTP_USER_AGENT'],"", "", "Error in connections.php: " . $e -> getMessage(), $_SERVER['REMOTE_ADDR']);
    echo "<error>" . $e->getMessage() . "</error>"; //error handling..
}

?>