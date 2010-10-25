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

/**
 * This is the API request handler
 */

include_once("DataStructs/StationsRequest.php");
include_once("InputHandlers/BRailStationsInput.php");
include_once("InputHandlers/NSStationsInput.php");

include_once("OutputHandlers/XMLStationsOutput.php");
include_once("OutputHandlers/JSONStationsOutput.php");

include_once("../includes/apiLog.php");

$lang = "EN";
$format = "xml";

//required vars, output error messages if empty
extract($_GET);

try {
    $request = new StationsRequest($lang);
    if($request -> getCountry() == "nl"){
        $input = new NSStationsInput();
    }else if($request -> getCountry() == "be"){
        $input = new BRailStationsInput();
    }else{
        //for now?
        $input = new BRailStationsInput();
    }
    $stations = $input -> execute($request);
    $output = null;
    if(strtolower($format) == "xml"){
        $output = new XMLStationsOutput($stations);
    }else if(strtolower($format) == "json"){
        $output = new JSONStationsOutput($stations);
    }else{
        throw new Exception("incorrect output type specified");
    }
    $output -> printAll();
    // Log request to database
    writeLog($_SERVER['HTTP_USER_AGENT'], "","", "none (stations.php)", $_SERVER['REMOTE_ADDR']);
}catch(Exception $e) {
    writeLog($_SERVER['HTTP_USER_AGENT'],"", "", "Error in stations.php: " . $e -> getMessage(), $_SERVER['REMOTE_ADDR']);
    echo "<error>" . $e->getMessage() . "</error>"; //error handling..
}


?>
