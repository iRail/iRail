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
 * This returns information about 1 specific station
 */
include_once("DataStructs/LiveboardRequest.php");
include_once("APICall.php");
include_once("ErrorHandlers/ErrorHandler.php");
date_default_timezone_set("Europe/Brussels");
$date = "";
$time = "";
$lang = "";
$arrdep = "";
$format = "xml";

//required vars, output error messages if empty
extract($_GET);

if ($lang == "") {
    $lang = "EN";
}

if ($arrdep == "") {
    $arrdep = "DEP";
}

if ($date == "") {
    $date = date("dmy");
}

//TODO: move this to constructor of LiveboardRequest
//reform date to needed train structure
preg_match("/(..)(..)(..)/si", $date, $m);
$date = "20" . $m[3] . $m[2] . $m[1];

if ($time == "") {
    $time = date("Hi");
}

//reform time to wanted structure
preg_match("/(..)(..)/si", $time, $m);
$time = $m[1] . ":" . $m[2];

if (!(isset($station))&& !(isset($id))) {
    $e = new Exception("You didn't use this right. You should specify the station", 1);
    $eh = new ErrorHandler($e, $format);
    $eh->printError();
} else if(!(isset($station))) {
    $request = new LiveboardRequest($id, $date, $time, $arrdep, $lang, $format, true);
    $call = new APICall("liveboard", $request);
    $call->executeCall();
} else {
    $request = new LiveboardRequest($station, $date, $time, $arrdep, $lang, $format, false);
    $call = new APICall("liveboard", $request);
    $call->executeCall();
}
?>
