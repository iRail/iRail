<?php
/*  Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
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

    Source available at http://github.com/Tuinslak/iRail
*/

// National query page

include("api/DataStructs/ConnectionRequest.php");
include("api/InputHandlers/BRailConnectionInput.php");
include("api/OutputHandlers/MobileWebOutput.php");

include("includes/apiLog.php");


$lang = "";
$timesel = "";
extract($_COOKIE);
extract($_POST);
$lang = $_COOKIE["language"];
// if bad stations, go back
if(!isset($_POST["from"]) || !isset($_POST["to"]) || $from == $to) {
	header('Location: ..');
}

// create time vars
$time = $h . ":". $m;
$date =  "20".$y. $mo .$d;

if(!isset($lang)) {
	$lang = "EN";
}

if(!isset($_POST["timesel"])){
    $timesel = "depart";
}

$results = 6;

$typeOfTransport = "all";

try {
    $request = new ConnectionRequest($from, $to, $time, $date, $timesel, $results, $lang, $typeOfTransport);
    $input = new BRailConnectionInput();
    $connections = $input -> execute($request);
    $output = new MobileWebOutput($connections);
    $output -> printAll();

    // Log request to database
    writeLog("willArriveAtWidget - " . $_SERVER['HTTP_USER_AGENT'], $connections[0] -> getDepart() -> getStation() -> getName(), $connections[0] -> getArrival() -> getStation() -> getName(), "none (iRail.be)", $_SERVER['REMOTE_ADDR']);
}catch(Exception $e) {
    writeLog("willArriveAtWidget - " . $_SERVER['HTTP_USER_AGENT'],"", "", "Error on willArriveAtWidget: " . $e -> getMessage(), $_SERVER['REMOTE_ADDR']);
    header('Location: noresults');
    echo $e->getMessage(); //error handling..
}

?>
